<?php
declare(strict_types=1);

final class DocumentController
{
    public static function handle(string $route): void
    {
        if ($route === 'document-download' || $route === 'document-delete') {
            $document = scoped('documents', id_param());
            if (!can_manage() && (int) $document['uploaded_by'] !== (int) current_user()['id']) abort_request(403, 'You do not have access to this document.');
            $path = config('storage') . '/uploads/' . $document['stored_name'];
            if ($route === 'document-delete') {
                post_only();
                Database::transaction(function () use ($document) {
                    if ($document['entry_id']) {
                        $entry = Database::one('SELECT * FROM entries WHERE id=? AND workspace_id=? FOR UPDATE', [$document['entry_id'], workspace_id()]);
                        if (!$entry || !Ledger::editable($entry)) throw new ValidationException('Documents attached to submitted or finalized entries cannot be removed.');
                    }
                    Database::query('DELETE FROM documents WHERE id=? AND workspace_id=?', [$document['id'], workspace_id()]);
                    audit('document.deleted', $document['original_name']);
                });
                if (is_file($path)) unlink($path);
                flash('Document removed.');
                redirect('documents');
            }
            if (!is_file($path)) abort_request(404, 'The document file is unavailable.');
            header('Content-Type: ' . $document['mime']);
            header('Content-Length: ' . filesize($path));
            header("Content-Disposition: attachment; filename=\"document\"; filename*=UTF-8''" . rawurlencode($document['original_name']));
            readfile($path);
            exit;
        }
        $error = null;
        try {
            if (is_post()) {
                $entryId = (int) input('entry_id') ?: null;
                if ($entryId) Ledger::entry($entryId);
                $upload = $_FILES['document'] ?? null;
                if (!$upload || is_array($upload['error']) || $upload['error'] !== UPLOAD_ERR_OK) throw new ValidationException('Choose a file up to 10 MB. The upload could not be completed.');
                if ($upload['size'] > 10 * 1024 * 1024 || $upload['size'] < 1) throw new ValidationException('Files must be between 1 byte and 10 MB.');
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
                $types = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!isset($types[$mime])) throw new ValidationException('Only PDF, JPG, PNG and WebP files are accepted.');
                if (str_starts_with($mime, 'image/') && !@getimagesize($upload['tmp_name'])) throw new ValidationException('This image file is invalid.');
                $filename = bin2hex(random_bytes(24)) . '.' . $types[$mime];
                $destination = config('storage') . '/uploads/' . $filename;
                if (!move_uploaded_file($upload['tmp_name'], $destination)) throw new RuntimeException('The document could not be stored.');
                try {
                    Database::transaction(function () use ($upload, $filename, $mime, $entryId) {
                        $original = mb_substr(str_replace(["\r", "\n", "\0"], '', basename($upload['name'])), 0, 190);
                        Database::insert('documents', ['workspace_id' => workspace_id(), 'uploaded_by' => current_user()['id'], 'entry_id' => $entryId, 'original_name' => $original, 'stored_name' => $filename, 'mime' => $mime, 'size' => $upload['size']]);
                        audit('document.uploaded', $original);
                    });
                } catch (Throwable $e) { unlink($destination); throw $e; }
                flash('Document uploaded.');
                redirect($entryId ? 'entry' : 'documents', $entryId ? ['id' => $entryId] : []);
            }
        } catch (ValidationException $e) { $error = $e->getMessage(); http_response_code(422); }
        $scope = can_manage() ? '' : ' AND d.uploaded_by=' . (int) current_user()['id'];
        $documents = Database::all("SELECT d.*,e.description entry_description,e.type entry_type FROM documents d LEFT JOIN entries e ON e.id=d.entry_id WHERE d.workspace_id=? $scope ORDER BY d.id DESC LIMIT 200", [workspace_id()]);
        [$entryScope, $params] = Ledger::visibility();
        $entries = Database::all("SELECT e.id,e.description,e.type FROM entries e WHERE workspace_id=? $entryScope ORDER BY e.id DESC LIMIT 200", [workspace_id(), ...$params]);
        render('documents', compact('documents', 'entries', 'error') + ['title' => 'Documents']);
    }
}
