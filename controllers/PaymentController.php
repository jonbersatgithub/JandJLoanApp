<?php
namespace Controllers;

use Core\Controller;
use Models\Payment;
use Models\Loan;

class PaymentController extends Controller {
    private $paymentModel;
    private $loanModel;
    
    public function __construct() {
        $this->paymentModel = new Payment();
        $this->loanModel = new Loan();
    }
    
    /**
     * Get all payments for a specific loan
     * GET /api/loans/{loanId}/payments
     */
    public function getLoanPayments($loanId) {
        try {
            // Check if loan exists
            $loan = $this->loanModel->find($loanId);
            if(!$loan) {
                return $this->error('Loan not found', 404);
            }
            
            $payments = $this->paymentModel->getPaymentsByLoan($loanId);
            $totalPaid = $this->paymentModel->getTotalPaidByLoan($loanId);
            $remainingBalance = $loan['loan_amount'] - $totalPaid;
            
            return $this->success('Payments retrieved successfully', [
                'loan' => $loan,
                'payments' => $payments,
                'total_paid' => $totalPaid,
                'remaining_balance' => max(0, $remainingBalance)
            ]);
        } catch(\Exception $e) {
            $this->logError('Failed to get loan payments', [
                'loan_id' => $loanId,
                'error' => $e->getMessage()
            ]);
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * Get single payment by ID
     * GET /api/payments/{id}
     */
    public function getPayment($id) {
        try {
            $payment = $this->paymentModel->find($id);
            if(!$payment) {
                return $this->error('Payment not found', 404);
            }
            
            // Get associated loan data
            $loan = $this->loanModel->find($payment['loan_id']);
            $payment['loan_details'] = $loan;
            
            return $this->success('Payment retrieved successfully', $payment);
        } catch(\Exception $e) {
            $this->logError('Failed to get payment', [
                'payment_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * Create new payment for a loan
     * POST /api/loans/{loanId}/payments
     */
    public function createPayment($loanId) {
        try {
            $data = $this->getRequestData();
            
            // Validate required fields
            $validation = $this->validateRequired($data, ['amount']);
            if($validation !== true) {
                return $this->error($validation, 400);
            }
            
            // Validate amount is numeric and positive
            $amount = floatval($data['amount']);
            if($amount <= 0) {
                return $this->error('Payment amount must be greater than 0', 400);
            }
            
            // Validate numeric
            $numericValidation = $this->validateNumeric($data, ['amount']);
            if($numericValidation !== true) {
                return $this->error($numericValidation, 400);
            }
            
            // Validate payment date if provided
            $paymentDate = $data['payment_date'] ?? date('Y-m-d');
            if(!$this->validateDate($paymentDate)) {
                return $this->error('Invalid payment date format. Use YYYY-MM-DD', 400);
            }
            
            // Create payment
            $paymentId = $this->paymentModel->createPayment($loanId, $amount, $paymentDate);
            
            if($paymentId) {
                $payment = $this->paymentModel->find($paymentId);
                $loan = $this->loanModel->getLoanWithPayments($loanId);
                
                return $this->success('Payment recorded successfully', [
                    'payment' => $payment,
                    'loan' => $loan
                ]);
            }
            
            return $this->error('Failed to record payment', 500);
        } catch(\Exception $e) {
            $this->logError('Failed to create payment', [
                'loan_id' => $loanId,
                'data' => $data ?? null,
                'error' => $e->getMessage()
            ]);
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * Update existing payment
     * PUT /api/payments/{id}
     */
    public function updatePayment($id) {
        try {
            $data = $this->getRequestData();
            
            // Check if payment exists
            $existingPayment = $this->paymentModel->find($id);
            if(!$existingPayment) {
                return $this->error('Payment not found', 404);
            }
            
            // Prepare update data
            $updateData = [];
            
            if(isset($data['amount'])) {
                $amount = floatval($data['amount']);
                if($amount <= 0) {
                    return $this->error('Payment amount must be greater than 0', 400);
                }
                $updateData['payment_amount'] = $amount;
            }
            
            if(isset($data['payment_date'])) {
                if(!$this->validateDate($data['payment_date'])) {
                    return $this->error('Invalid payment date format. Use YYYY-MM-DD', 400);
                }
                $updateData['payment_date'] = $data['payment_date'];
            }
            
            if(empty($updateData)) {
                return $this->error('No data to update', 400);
            }
            
            // Update payment
            $updated = $this->paymentModel->update($id, $updateData);
            
            if($updated) {
                // Recalculate remaining balance for the loan
                $loan = $this->loanModel->find($existingPayment['loan_id']);
                $totalPaid = $this->paymentModel->getTotalPaidByLoan($loan['id']);
                $newRemainingBalance = $loan['loan_amount'] - $totalPaid;
                
                // Update remaining balance in the last payment
                $payments = $this->paymentModel->getPaymentsByLoan($loan['id']);
                if(!empty($payments)) {
                    $lastPayment = $payments[0]; // Most recent payment
                    $this->paymentModel->update($lastPayment['id'], [
                        'remaining_balance' => max(0, $newRemainingBalance)
                    ]);
                }
                
                // Update loan status if fully paid
                if($newRemainingBalance <= 0) {
                    $this->loanModel->update($loan['id'], ['status' => Loan::STATUS_PAID]);
                } elseif($loan['status'] === Loan::STATUS_PAID) {
                    $this->loanModel->update($loan['id'], ['status' => Loan::STATUS_ACTIVE]);
                }
                
                $payment = $this->paymentModel->find($id);
                return $this->success('Payment updated successfully', $payment);
            }
            
            return $this->error('Failed to update payment', 500);
        } catch(\Exception $e) {
            $this->logError('Failed to update payment', [
                'payment_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * Delete payment
     * DELETE /api/payments/{id}
     */
    public function deletePayment($id) {
        try {
            // Check if payment exists
            $payment = $this->paymentModel->find($id);
            if(!$payment) {
                return $this->error('Payment not found', 404);
            }
            
            $loanId = $payment['loan_id'];
            
            // Start transaction
            $this->paymentModel->beginTransaction();
            
            try {
                // Delete payment
                $deleted = $this->paymentModel->delete($id);
                
                if($deleted) {
                    // Recalculate remaining balance
                    $totalPaid = $this->paymentModel->getTotalPaidByLoan($loanId);
                    $loan = $this->loanModel->find($loanId);
                    $newRemainingBalance = $loan['loan_amount'] - $totalPaid;
                    
                    // Update remaining balance in the latest payment
                    $payments = $this->paymentModel->getPaymentsByLoan($loanId);
                    if(!empty($payments)) {
                        $lastPayment = $payments[0];
                        $this->paymentModel->update($lastPayment['id'], [
                            'remaining_balance' => max(0, $newRemainingBalance)
                        ]);
                    }
                    
                    // Update loan status
                    if($newRemainingBalance <= 0) {
                        $this->loanModel->update($loanId, ['status' => Loan::STATUS_PAID]);
                    } elseif($newRemainingBalance > 0 && $loan['status'] === Loan::STATUS_PAID) {
                        $this->loanModel->update($loanId, ['status' => Loan::STATUS_ACTIVE]);
                    }
                    
                    $this->paymentModel->commit();
                    return $this->success('Payment deleted successfully');
                }
                
                $this->paymentModel->rollback();
                return $this->error('Failed to delete payment', 500);
            } catch(\Exception $e) {
                $this->paymentModel->rollback();
                throw $e;
            }
        } catch(\Exception $e) {
            $this->logError('Failed to delete payment', [
                'payment_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * Get payment summary for a loan
     * GET /api/loans/{loanId}/payments/summary
     */
    public function getPaymentSummary($loanId) {
        try {
            // Check if loan exists
            $loan = $this->loanModel->find($loanId);
            if(!$loan) {
                return $this->error('Loan not found', 404);
            }
            
            $totalPaid = $this->paymentModel->getTotalPaidByLoan($loanId);
            $remainingBalance = $loan['loan_amount'] - $totalPaid;
            $payments = $this->paymentModel->getPaymentsByLoan($loanId);
            
            // Calculate payment statistics
            $paymentCount = count($payments);
            $averagePayment = $paymentCount > 0 ? $totalPaid / $paymentCount : 0;
            $lastPaymentDate = !empty($payments) ? $payments[0]['payment_date'] : null;
            
            // Calculate expected payments
            $expectedTotal = $loan['monthly_payment'] * $loan['loan_term'];
            $paymentProgress = ($totalPaid / $loan['loan_amount']) * 100;
            
            // Get payment schedule
            $paymentSchedule = $this->paymentModel->getPaymentSchedule($loanId);
            
            // Calculate next payment due
            $nextPaymentDue = null;
            if($loan['status'] === Loan::STATUS_ACTIVE && $remainingBalance > 0) {
                $lastPayment = !empty($payments) ? $payments[0] : null;
                $startDate = new \DateTime($loan['start_date']);
                $currentDate = new \DateTime();
                
                if($lastPayment) {
                    $lastPaymentDate = new \DateTime($lastPayment['payment_date']);
                    $nextPaymentDue = $lastPaymentDate->modify('+1 month')->format('Y-m-d');
                } else {
                    $nextPaymentDue = $startDate->format('Y-m-d');
                }
                
                // If next payment date is in the past, calculate from current date
                if($nextPaymentDue < $currentDate->format('Y-m-d')) {
                    $nextPaymentDue = $currentDate->modify('+1 month')->format('Y-m-d');
                }
            }
            
            return $this->success('Payment summary retrieved', [
                'loan' => [
                    'id' => $loan['id'],
                    'borrower_name' => $loan['borrower_name'],
                    'loan_amount' => $loan['loan_amount'],
                    'monthly_payment' => $loan['monthly_payment'],
                    'status' => $loan['status']
                ],
                'summary' => [
                    'total_paid' => $totalPaid,
                    'remaining_balance' => max(0, $remainingBalance),
                    'payment_count' => $paymentCount,
                    'average_payment' => round($averagePayment, 2),
                    'last_payment_date' => $lastPaymentDate,
                    'next_payment_due' => $nextPaymentDue,
                    'progress_percentage' => round($paymentProgress, 2),
                    'expected_total' => $expectedTotal,
                    'saved_interest' => max(0, $expectedTotal - $totalPaid)
                ],
                'payment_schedule' => $paymentSchedule,
                'recent_payments' => array_slice($payments, 0, 5)
            ]);
        } catch(\Exception $e) {
            $this->logError('Failed to get payment summary', [
                'loan_id' => $loanId,
                'error' => $e->getMessage()
            ]);
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * Get all payments across all loans (with pagination)
     * GET /api/payments?page=1&limit=20
     */
    public function getAllPayments() {
        try {
            $page = (int)($this->getParam('page', 1));
            $limit = (int)($this->getParam('limit', 20));
            $loanId = $this->getParam('loan_id', null);
            
            // Get payments
            if($loanId) {
                $payments = $this->paymentModel->getPaymentsByLoan($loanId);
            } else {
                // For all payments, we need to implement a method in Payment model
                $query = "SELECT p.*, l.borrower_name, l.loan_amount 
                         FROM payments p 
                         JOIN loans l ON p.loan_id = l.id 
                         ORDER BY p.payment_date DESC";
                $stmt = $this->paymentModel->db->prepare($query);
                $stmt->execute();
                $payments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
            
            // Apply pagination
            $paginated = $this->paginate($payments, $page, $limit);
            
            return $this->success('Payments retrieved successfully', $paginated);
        } catch(\Exception $e) {
            $this->logError('Failed to get all payments', [
                'error' => $e->getMessage()
            ]);
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * Export payments to CSV
     * GET /api/loans/{loanId}/payments/export
     */
    public function exportPayments($loanId) {
        try {
            // Check if loan exists
            $loan = $this->loanModel->find($loanId);
            if(!$loan) {
                return $this->error('Loan not found', 404);
            }
            
            $payments = $this->paymentModel->getPaymentsByLoan($loanId);
            
            if(empty($payments)) {
                return $this->error('No payments to export', 404);
            }
            
            // Format data for CSV
            $exportData = [];
            foreach($payments as $payment) {
                $exportData[] = [
                    'Payment ID' => $payment['id'],
                    'Payment Date' => $payment['payment_date'],
                    'Payment Amount' => $payment['payment_amount'],
                    'Remaining Balance' => $payment['remaining_balance'],
                    'Loan ID' => $loan['id'],
                    'Borrower Name' => $loan['borrower_name'],
                    'Loan Amount' => $loan['loan_amount'],
                    'Interest Rate' => $loan['interest_rate']
                ];
            }
            
            $filename = "payments_loan_{$loanId}_{$loan['borrower_name']}_" . date('Y-m-d') . ".csv";
            $this->exportCSV($exportData, $filename);
        } catch(\Exception $e) {
            $this->logError('Failed to export payments', [
                'loan_id' => $loanId,
                'error' => $e->getMessage()
            ]);
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * Get payment statistics across all loans
     * GET /api/payments/statistics
     */
    public function getPaymentStatistics() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_payments,
                        SUM(payment_amount) as total_amount,
                        AVG(payment_amount) as average_payment,
                        MAX(payment_amount) as max_payment,
                        MIN(payment_amount) as min_payment,
                        DATE_FORMAT(payment_date, '%Y-%m') as payment_month
                      FROM payments 
                      GROUP BY payment_month
                      ORDER BY payment_month DESC
                      LIMIT 12";
            
            $stmt = $this->paymentModel->db->prepare($query);
            $stmt->execute();
            $monthlyStats = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Get overall statistics
            $query2 = "SELECT 
                        COUNT(*) as total_payments,
                        SUM(payment_amount) as total_collected,
                        COUNT(DISTINCT loan_id) as loans_with_payments,
                        AVG(payment_amount) as avg_payment
                      FROM payments";
            
            $stmt2 = $this->paymentModel->db->prepare($query2);
            $stmt2->execute();
            $overallStats = $stmt2->fetch(\PDO::FETCH_ASSOC);
            
            return $this->success('Payment statistics retrieved', [
                'overall' => $overallStats,
                'monthly_breakdown' => $monthlyStats
            ]);
        } catch(\Exception $e) {
            $this->logError('Failed to get payment statistics', [
                'error' => $e->getMessage()
            ]);
            return $this->error($e->getMessage());
        }
    }
}
?>