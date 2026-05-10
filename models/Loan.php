<?php
namespace Models;

use Core\Model;

class Loan extends Model {
    protected $table = 'loans';
    protected $primaryKey = 'id';
    
    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_PAID = 'paid';
    const STATUS_DEFAULTED = 'defaulted';
    
    // Create new loan
    public function createLoan($data) {
        // $monthlyPayment = $this->calculateMonthlyPayment(
        //     $data['loan_amount'],
        //     $data['interest_rate'],
        //     $data['loan_term']
        // );
        
        $dueDate = date('Y-m-d', strtotime($data['start_date'] . ' + ' . $data['loan_term'] . ' months'));
        
        $loanData = [
            'borrower_name' => $data['borrower_name'],
            'loan_amount' => $data['loan_amount'],
            'interest_rate' => $data['interest_rate'],
            'loan_term' => $data['loan_term'],
            'monthly_payment' => $data['monthly_payment'] ?? null,
            'status' => $data['status'] ?? self::STATUS_ACTIVE,
            'start_date' => $data['start_date'],
            'due_date' => $dueDate,
            'interest_type' => $data['interest_type']
        ];
        
        return $this->create($loanData);
    }
    
    // Update existing loan
    public function updateLoan($id, $data) {
        if(isset($data['loan_amount']) || isset($data['interest_rate']) || isset($data['loan_term'])) {
            $currentLoan = $this->find($id);
            $loanAmount = $data['loan_amount'] ?? $currentLoan['loan_amount'];
            $interestRate = $data['interest_rate'] ?? $currentLoan['interest_rate'];
            $loanTerm = $data['loan_term'] ?? $currentLoan['loan_term'];
            
            $data['monthly_payment'] = $this->calculateMonthlyPayment($loanAmount, $interestRate, $loanTerm);
            
            if(isset($data['start_date']) && isset($data['loan_term'])) {
                $data['due_date'] = date('Y-m-d', strtotime($data['start_date'] . ' + ' . $data['loan_term'] . ' months'));
            } elseif(isset($data['start_date'])) {
                $startDate = $data['start_date'];
                $loanTerm = $currentLoan['loan_term'];
                $data['due_date'] = date('Y-m-d', strtotime($startDate . ' + ' . $loanTerm . ' months'));
            }
        }
        
        return $this->update($id, $data);
    }
    
    // Get loan with payments
    public function getLoanWithPayments($id) {
        $loan = $this->find($id);
        if(!$loan) return null;
        
        $paymentModel = new Payment();
        $loan['payments'] = $paymentModel->getPaymentsByLoan($id);
        $loan['total_paid'] = array_sum(array_column($loan['payments'], 'payment_amount'));
        $loan['remaining_balance'] = $loan['loan_amount'] - $loan['total_paid'];
        
        return $loan;
    }
    
    // Get statistics
    public function getStatistics() {
        $totalLoans = $this->count();
        $activeLoans = $this->count("status = :status", [':status' => self::STATUS_ACTIVE]);
        
        $query = "SELECT SUM(loan_amount) as total_amount FROM {$this->table}";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $totalAmount = $stmt->fetch(\PDO::FETCH_ASSOC)['total_amount'] ?? 0;
        
        $query = "SELECT SUM(monthly_payment) as total_monthly_payment FROM {$this->table} WHERE status = :status";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':status' => self::STATUS_ACTIVE]);
        $totalMonthlyPayment = $stmt->fetch(\PDO::FETCH_ASSOC)['total_monthly_payment'] ?? 0;
        
        return [
            'total_loans' => (int)$totalLoans,
            'active_loans' => (int)$activeLoans,
            'total_amount' => (float)$totalAmount,
            'total_monthly_payment' => (float)$totalMonthlyPayment
        ];
    }
    
    // Search loans
    public function search($keyword) {
        $query = "SELECT * FROM {$this->table} WHERE borrower_name LIKE :keyword OR id LIKE :keyword";
        $stmt = $this->db->prepare($query);
        $keyword = "%{$keyword}%";
        $stmt->execute([':keyword' => $keyword]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
?>