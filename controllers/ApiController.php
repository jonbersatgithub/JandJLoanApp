<?php
namespace App\Controllers;

use App\Models\Loan;

class ApiController {
    private $loanModel;
    
    public function __construct() {
        $this->loanModel = new Loan();
    }
    
    public function getLoans() {
        try {
            $loans = $this->loanModel->all();
            echo json_encode([
                'success' => true,
                'data' => ['loans' => $loans]
            ]);
        } catch(\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function createLoan() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Calculate monthly payment
        $monthlyRate = ($input['interest_rate'] / 100) / 12;
        $months = $input['loan_term'];
        $amount = $input['loan_amount'];
        
        if ($monthlyRate == 0) {
            $monthlyPayment = $amount / $months;
        } else {
            $monthlyPayment = $amount * ($monthlyRate * pow(1 + $monthlyRate, $months)) / 
                             (pow(1 + $monthlyRate, $months) - 1);
        }
        
        $input['monthly_payment'] = round($monthlyPayment, 2);
        $input['due_date'] = date('Y-m-d', strtotime($input['start_date'] . ' + ' . $months . ' months'));
        
        $result = $this->loanModel->create($input);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Loan created successfully' : 'Failed to create loan'
        ]);
    }
    
    public function getStatistics() {
        try {
            $stats = $this->loanModel->getStatistics();
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } catch(\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
?>