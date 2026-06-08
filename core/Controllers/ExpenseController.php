<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SessionManager;
use Exception;
use PDO;

class ExpenseController extends BaseHotelController
{
    public function index(): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);

        $db = Database::getConnection();

        $month = $_GET['month'] ?? date('Y-m');

        $stmt = $db->prepare("
            SELECT * FROM expenses 
            WHERE hotel_id = :hotel_id AND DATE_FORMAT(date, '%Y-%m') = :month
            ORDER BY date DESC, created_at DESC
        ");
        $stmt->execute(['hotel_id' => $this->hotelId, 'month' => $month]);
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('user/expenses', [
            'pageTitle' => 'Expense Tracking',
            'expenses'  => $expenses,
            'month'     => $month
        ]);
    }

    public function store(array $post): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);
        $this->validateCsrf($post);

        $category = trim($post['category'] ?? '');
        $amount = (float)($post['amount'] ?? 0);
        $date = trim($post['date'] ?? date('Y-m-d'));
        $description = trim($post['description'] ?? '');

        if ($amount <= 0 || empty($category) || empty($date)) {
            $this->redirect('/user/expenses?error=' . urlencode('Invalid expense data.'));
            return;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO expenses (hotel_id, category, amount, date, description)
                VALUES (:hotel_id, :category, :amount, :date, :description)
            ");
            $stmt->execute([
                'hotel_id' => $this->hotelId,
                'category' => $category,
                'amount' => $amount,
                'date' => $date,
                'description' => $description
            ]);

            $this->redirect('/user/expenses?success=' . urlencode('Expense added successfully.'));
        } catch (Exception $e) {
            $this->redirect('/user/expenses?error=' . urlencode('Error adding expense.'));
        }
    }

    public function delete(array $post): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($post);

        $id = (int)($post['expense_id'] ?? 0);

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM expenses WHERE id = :id AND hotel_id = :hotel_id");
            $stmt->execute(['id' => $id, 'hotel_id' => $this->hotelId]);

            $this->redirect('/user/expenses?success=' . urlencode('Expense deleted.'));
        } catch (Exception $e) {
            $this->redirect('/user/expenses?error=' . urlencode('Error deleting expense.'));
        }
    }
}
