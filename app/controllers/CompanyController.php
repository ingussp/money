<?php
declare(strict_types=1);

class CompanyController extends Controller
{
    public function index(): void
    {
        require_login();
        $companies = db()->query('SELECT c.*, (SELECT COUNT(*) FROM documents d WHERE d.company_id = c.id) AS doc_count FROM companies c ORDER BY c.id')->fetchAll();
        $this->view('companies/index', [
            'title' => t('companies'),
            'activeRoute' => 'companies',
            'companies' => $companies,
        ]);
    }

    public function create(): void
    {
        require_login();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $name = trim($_POST['name'] ?? '');
            $reg = trim($_POST['reg_number'] ?? '');
            if ($name === '') {
                flash('error', 'Company name is required.');
                redirect('companies/create');
            }
            $stmt = db()->prepare('INSERT INTO companies (name, reg_number) VALUES (?, ?)');
            $stmt->execute([$name, $reg]);
            log_action('create company: ' . $name);
            flash('success', 'Company created.');
            redirect('companies');
        }
        $this->view('companies/form', [
            'title' => t('new_company'),
            'activeRoute' => 'companies',
            'company' => null,
        ]);
    }

    public function edit(): void
    {
        require_login();
        $id = (int)($_GET['id'] ?? 0);
        $company = $this->findCompany($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $name = trim($_POST['name'] ?? '');
            $reg = trim($_POST['reg_number'] ?? '');
            $stmt = db()->prepare('UPDATE companies SET name = ?, reg_number = ? WHERE id = ?');
            $stmt->execute([$name, $reg, $id]);
            log_action('edit company: ' . $name);
            flash('success', 'Company updated.');
            redirect('companies');
        }

        $this->view('companies/form', [
            'title' => t('edit'),
            'activeRoute' => 'companies',
            'company' => $company,
        ]);
    }

    public function delete(): void
    {
        require_role('admin', 'accountant');
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM companies WHERE id = ?')->execute([$id]);
        log_action('delete company #' . $id);
        flash('success', 'Company deleted.');
        redirect('companies');
    }

    private function findCompany(int $id): array
    {
        $stmt = db()->prepare('SELECT * FROM companies WHERE id = ?');
        $stmt->execute([$id]);
        $company = $stmt->fetch();
        if (!$company) {
            http_response_code(404);
            exit('Company not found.');
        }
        return $company;
    }
}
