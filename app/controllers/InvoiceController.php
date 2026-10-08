<?php
declare(strict_types=1);

final class InvoiceController
{
    public static function handle(string $route): void
    {
        require_manager();
        if ($route === 'invoice-action') self::action();
        if ($route === 'invoices') {
            $invoices = Database::all('SELECT * FROM invoices WHERE workspace_id=? ORDER BY id DESC LIMIT 200', [workspace_id()]);
            render('invoices', ['title' => 'Invoices', 'invoices' => $invoices]);
            return;
        }
        $invoice = id_param() ? scoped('invoices', id_param()) : null;
        if ($route === 'invoice-print') {
            if (!$invoice) abort_request(404, 'Invoice not found');
            $items = Database::all('SELECT * FROM invoice_items WHERE invoice_id=? ORDER BY id', [$invoice['id']]);
            render('invoice-print', compact('invoice', 'items') + ['title' => $invoice['number']], 'print');
            return;
        }
        $error = null;
        try {
            if (is_post()) {
                if ($invoice) throw new ValidationException('Issued invoice records cannot be overwritten. Cancel a draft and create a replacement.');
                $contact = scoped('contacts', (int) required('contact_id'));
                $number = required('number', 80);
                $issueDate = date_value(required('issue_date'));
                $dueDate = date_value(required('due_date'));
                if ($dueDate < $issueDate) throw new ValidationException('The due date must not be before the issue date.');
                if (Database::one('SELECT id FROM invoices WHERE workspace_id=? AND number=?', [workspace_id(), $number])) throw new ValidationException('This invoice number is already in use.');
                $descriptions = $_POST['item_description'] ?? [];
                if (!is_array($descriptions) || count($descriptions) < 1 || count($descriptions) > 50) throw new ValidationException('Add between 1 and 50 invoice items.');
                $items = [];
                $subtotal = $tax = 0;
                foreach ($descriptions as $key => $description) {
                    if (!is_string($description) || trim($description) === '' || mb_strlen($description) > 190) throw new ValidationException('Every item needs a description of up to 190 characters.');
                    $quantity = cents((string) ($_POST['quantity'][$key] ?? ''));
                    if ($quantity > 1000000) throw new ValidationException('The maximum quantity is 10,000.');
                    $unitPrice = cents((string) ($_POST['unit_price'][$key] ?? ''), true);
                    if ($unitPrice > 1000000000) throw new ValidationException('The maximum unit price is 10,000,000.');
                    $rate = cents((string) ($_POST['tax_rate'][$key] ?? '0'), true);
                    if ($rate > 10000) throw new ValidationException('The tax rate must be between 0 and 100.');
                    $lineSubtotal = intdiv($quantity * $unitPrice + 50, 100);
                    $lineTax = intdiv($lineSubtotal * $rate + 5000, 10000);
                    $subtotal += $lineSubtotal;
                    $tax += $lineTax;
                    $items[] = ['description' => trim($description), 'quantity_hundredths' => $quantity, 'unit_price' => $unitPrice, 'tax_basis_points' => $rate, 'subtotal' => $lineSubtotal, 'tax_amount' => $lineTax];
                }
                if ($subtotal + $tax < 1 || $subtotal + $tax > 99999999999) throw new ValidationException('The invoice total is outside the supported range.');
                $notes = input('notes');
                if (mb_strlen($notes) > 5000) throw new ValidationException('Notes must be at most 5,000 characters.');
                $id = Database::transaction(function () use ($contact, $number, $issueDate, $dueDate, $subtotal, $tax, $items, $notes) {
                    $company = workspace();
                    $id = Database::insert('invoices', ['workspace_id' => workspace_id(), 'contact_id' => $contact['id'], 'number' => $number, 'issue_date' => $issueDate, 'due_date' => $dueDate, 'subtotal' => $subtotal, 'tax_amount' => $tax, 'total' => $subtotal + $tax, 'notes' => $notes, 'seller_name' => $company['name'], 'seller_address' => $company['address'], 'seller_registration' => $company['registration_number'], 'customer_name' => $contact['name'], 'customer_address' => $contact['address'], 'customer_registration' => $contact['registration_number']]);
                    foreach ($items as $item) Database::insert('invoice_items', ['invoice_id' => $id] + $item);
                    audit('invoice.created', $number);
                    return $id;
                });
                flash('Invoice saved as a draft.');
                redirect('invoice', ['id' => $id]);
            }
        } catch (ValidationException $e) { $error = $e->getMessage(); http_response_code(422); }
        $contacts = Database::all('SELECT * FROM contacts WHERE workspace_id=? ORDER BY name', [workspace_id()]);
        $items = $invoice ? Database::all('SELECT * FROM invoice_items WHERE invoice_id=? ORDER BY id', [$invoice['id']]) : [];
        render('invoice', compact('invoice', 'contacts', 'items', 'error') + ['title' => $invoice ? $invoice['number'] : 'New invoice']);
    }

    private static function action(): never
    {
        post_only();
        $invoice = scoped('invoices', id_param());
        $action = input('action');
        Database::transaction(function () use ($invoice, $action) {
            $invoice = Database::one('SELECT * FROM invoices WHERE id=? AND workspace_id=? FOR UPDATE', [$invoice['id'], workspace_id()]);
            if ($action === 'sent' && $invoice['status'] === 'draft') {
                Database::query("UPDATE invoices SET status='sent' WHERE id=?", [$invoice['id']]);
            } elseif ($action === 'cancel' && in_array($invoice['status'], ['draft', 'sent'], true)) {
                Database::query("UPDATE invoices SET status='cancelled' WHERE id=?", [$invoice['id']]);
            } elseif ($action === 'paid' && $invoice['status'] === 'sent' && !$invoice['entry_id']) {
                $paidOn = date_value(required('paid_on'));
                $category = Database::one("SELECT id FROM categories WHERE workspace_id=? AND type='income' AND name='Sales'", [workspace_id()]);
                $id = Database::insert('entries', ['workspace_id' => workspace_id(), 'created_by' => current_user()['id'], 'type' => 'income', 'description' => 'Invoice ' . $invoice['number'] . ' - ' . $invoice['customer_name'], 'contact_id' => $invoice['contact_id'], 'category_id' => $category['id'] ?? null, 'amount' => $invoice['total'], 'tax_amount' => $invoice['tax_amount'], 'entry_date' => $invoice['issue_date'], 'paid_on' => $paidOn, 'status' => 'paid', 'reference' => $invoice['number']]);
                Database::query("UPDATE invoices SET status='paid',entry_id=? WHERE id=?", [$id, $invoice['id']]);
            } else {
                throw new ValidationException('This invoice status change is not allowed.');
            }
            audit('invoice.' . $action, $invoice['number']);
        });
        flash($action === 'paid' ? 'Payment recorded in Income.' : ($action === 'sent' ? 'Invoice marked as sent. No email was sent.' : 'Invoice cancelled.'));
        redirect('invoice', ['id' => $invoice['id']]);
    }
}
