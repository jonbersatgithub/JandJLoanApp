<?php
namespace Models;

use Core\Model;

class Payment extends Model {
    protected $table = 'payments';
    protected $primaryKey = 'id';
    
    // Create payment for loan
    public function createPayment($loanId, $amount, $paymentDate = null) {
        if(!$paymentDate) {
            $paymentDate = date('Y-m-d');
        }
        
        $loanModel = new Loan();
        $loan = $loanModel->find($loanId);
        
        if(!$loan) {
            throw new \Exception("Loan not found");
        }
        
        $totalPaid = $this->getTotalPaidByLoan($loanId);
        $remainingBalance = $loan['loan_amount'] - $totalPaid;
        
        if($amount > $remainingBalance) {
            throw new \Exception("Payment amount exceeds remaining balance");
        }
        
        $newRemainingBalance = $remainingBalance - $amount;
        
        $paymentData = [
            'loan_id' => $loanId,
            'payment_amount' => $amount,
            'payment_date' => $paymentDate,
            'remaining_balance' => $newRemainingBalance
        ];
        
        $this->beginTransaction();
        
        try {
            $paymentId = $this->create($paymentData);
            
            // Update loan status if fully paid
            if($newRemainingBalance <= 0) {
                $loanModel->update($loanId, ['status' => Loan::STATUS_PAID]);
            }
            
            $this->commit();
            return $paymentId;
        } catch(\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    // Get payments by loan
    public function getPaymentsByLoan($loanId) {
        return $this->where("loan_id = :loan_id ORDER BY payment_date DESC", [':loan_id' => $loanId]);
    }
    
    // Get total paid for loan
    public function getTotalPaidByLoan($loanId) {
        $query = "SELECT SUM(payment_amount) as total FROM {$this->table} WHERE loan_id = :loan_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':loan_id' => $loanId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (float)($result['total'] ?? 0);
    }
    
    // Get payment schedule for loan
    public function getPaymentSchedule($loanId) {
        $loanModel = new Loan();
        $loan = $loanModel->find($loanId);
        
        if(!$loan) {
            return null;
        }
        
        $schedule = [];
        $balance = $loan['loan_amount'];
        $monthlyPayment = $loan['monthly_payment'];
        $monthlyRate = ($loan['interest_rate'] / 100) / 12;
        
        for($i = 1; $i <= $loan['loan_term']; $i++) {
            $interestPayment = $balance * $monthlyRate;
            $principalPayment = $monthlyPayment - $interestPayment;
            $balance -= $principalPayment;
            
            $schedule[] = [
                'month' => $i,
                'payment_amount' => round($monthlyPayment, 2),
                'principal' => round($principalPayment, 2),
                'interest' => round($interestPayment, 2),
                'remaining_balance' => max(0, round($balance, 2))
            ];
        }
        
        return $schedule;
    }
}
?>