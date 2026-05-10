<?php
namespace Controllers;

use Core\Controller;
use Models\Loan;
use Models\Payment;

class LoanController extends Controller {
    private $loanModel;
    private $paymentModel;
    
    public function __construct() {
        $this->loanModel = new Loan();
        $this->paymentModel = new Payment();
    }
    
    // Get all loans
    public function index() {
        try {
            $loans = $this->loanModel->findAll();
            $statistics = $this->loanModel->getStatistics();
            
            return $this->success('Loans retrieved successfully', [
                'loans' => $loans,
                'statistics' => $statistics
            ]);
        } catch(\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    // Get single loan
    public function show($id) {
        try {
            $loan = $this->loanModel->getLoanWithPayments($id);
            if(!$loan) {
                return $this->error('Loan not found', 404);
            }
            return $this->success('Loan retrieved successfully', $loan);
        } catch(\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    // Create loan
    public function store() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if(!$data) {
                $data = $_POST;
            }
            
            $data = $this->sanitize($data);

            // echo "<pre>";
            // print_r($data);
            // exit;
            
            $validation = $this->validateRequired($data, [
                'borrower_name', 'loan_amount', 'interest_rate', 'loan_term', 'start_date', 'interest_type', 'monthly_payment'
            ]);
            
            if($validation !== true) {
                return $this->error($validation);
            }
            
            $loanId = $this->loanModel->createLoan($data);
            
            if($loanId) {
                $loan = $this->loanModel->find($loanId);
                return $this->success('Loan created successfully', $loan);
            }
            
            return $this->error('Failed to create loan');
        } catch(\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    // Update loan
    public function update($id) {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if(!$data) {
                $data = $_POST;
            }
            
            $data = $this->sanitize($data);
            
            if(empty($data)) {
                return $this->error('No data to update');
            }
            
            $updated = $this->loanModel->updateLoan($id, $data);
            
            if($updated) {
                $loan = $this->loanModel->find($id);
                return $this->success('Loan updated successfully', $loan);
            }
            
            return $this->error('Failed to update loan');
        } catch(\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    // Delete loan
    public function destroy($id) {
        try {
            $deleted = $this->loanModel->delete($id);
            
            if($deleted) {
                return $this->success('Loan deleted successfully');
            }
            
            return $this->error('Failed to delete loan');
        } catch(\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    // Search loans
    public function search() {
        try {
            $keyword = $_GET['q'] ?? '';
            if(empty($keyword)) {
                return $this->error('Search keyword is required');
            }
            
            $loans = $this->loanModel->search($keyword);
            return $this->success('Search results retrieved', $loans);
        } catch(\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    // Get statistics
    public function statistics() {
        try {
            $statistics = $this->loanModel->getStatistics();
            return $this->success('Statistics retrieved successfully', $statistics);
        } catch(\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    // Make payment
    public function makePayment($loanId) {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if(!$data) {
                $data = $_POST;
            }
            
            $data = $this->sanitize($data);
            
            $validation = $this->validateRequired($data, ['amount']);
            if($validation !== true) {
                return $this->error($validation);
            }
            
            $paymentId = $this->paymentModel->createPayment(
                $loanId,
                $data['amount'],
                $data['payment_date'] ?? null
            );
            
            if($paymentId) {
                $loan = $this->loanModel->getLoanWithPayments($loanId);
                return $this->success('Payment recorded successfully', $loan);
            }
            
            return $this->error('Failed to record payment');
        } catch(\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    // Get payment schedule
    public function paymentSchedule($loanId) {
        try {
            $schedule = $this->paymentModel->getPaymentSchedule($loanId);
            if($schedule === null) {
                return $this->error('Loan not found', 404);
            }
            return $this->success('Payment schedule retrieved', $schedule);
        } catch(\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
?>