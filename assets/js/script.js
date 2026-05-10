let currentLoans = [];
let dataTable = null;

// Initialize on page load
$(document).ready(function () {
  loadLoans();
  loadStatistics();

  // Setup event listeners
  $("#searchInput").on("input", handleSearch);
  $("#loanForm").on("submit", handleLoanSubmit);
  $("#paymentForm").on("submit", handlePaymentSubmit);

  // Calculate monthly payment preview
  $("#loanAmount, #interestRate, #loanTerm").on(
    "input",
    calculateMonthlyPreview,
  );

  // Initialize Bootstrap tooltips
  $('[data-bs-toggle="tooltip"]').tooltip();
});

// Load loans and initialize DataTable
async function loadLoans() {
  try {
    const response = await LoanAPI.getLoans();
    if (response.success) {
      currentLoans = response.data.loans;
      renderLoansTable(currentLoans);
    }
  } catch (error) {
    showError("Failed to load loans: " + error.message);
  }
}

// Render loans table with DataTable
function renderLoansTable(loans) {
  const tbody = $("#loansTableBody");

  if (!loans || loans.length === 0) {
    tbody.html(
      '<tr><td colspan="10" class="text-center">No loans found</td></tr>',
    );
    return;
  }

  tbody.html(
    loans
      .map(
        (loan) => `
        <tr>
            <td>${loan.id}</td>
            <td><strong>${escapeHtml(loan.borrower_name)}</strong></td>
            <td>${formatCurrency(loan.loan_amount)}</td>
            <td>${loan.interest_rate}%</td>
            <td>${loan.loan_term} mo</td>
            <td>${formatCurrency(loan.monthly_payment)}</td>
            <td>${formatCurrency(loan.total_paid || 0)}</td>
            <td>${formatCurrency(loan.remaining_balance || loan.loan_amount)}</td>
            <td><span class="status-badge status-${loan.status}">${loan.status}</span></td>
            <td>
                <button class="btn btn-sm btn-info btn-action" onclick="viewLoan(${loan.id})" data-bs-toggle="tooltip" title="View Details">
                    <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-sm btn-warning btn-action" onclick="editLoan(${loan.id})" data-bs-toggle="tooltip" title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-success btn-action" onclick="recordPayment(${loan.id})" data-bs-toggle="tooltip" title="Record Payment">
                    <i class="bi bi-cash"></i>
                </button>
                <button class="btn btn-sm btn-danger btn-action" onclick="deleteLoan(${loan.id})" data-bs-toggle="tooltip" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `,
      )
      .join(""),
  );

  // Initialize tooltips for new buttons
  $('[data-bs-toggle="tooltip"]').tooltip();

  // Initialize DataTable if not already initialized
  if ($.fn.dataTable.isDataTable("#loansTable")) {
    dataTable.destroy();
    dataTable = $("#loansTable").DataTable({
      pageLength: 10,
      responsive: true,
      order: [[0, "desc"]],
      language: {
        search: "Search:",
        lengthMenu: "Show _MENU_ entries",
        info: "Showing _START_ to _END_ of _TOTAL_ entries",
      },
    });
  } else {
    dataTable = $("#loansTable").DataTable({
      pageLength: 10,
      responsive: true,
      order: [[0, "desc"]],
      language: {
        search: "Search:",
        lengthMenu: "Show _MENU_ entries",
        info: "Showing _START_ to _END_ of _TOTAL_ entries",
      },
    });
  }
}

// Load statistics
async function loadStatistics() {
  try {
    const response = await LoanAPI.getStatistics();
    if (response.success) {
      const stats = response.data;
      $("#totalLoans").text(stats.total_loans);
      $("#activeLoans").text(stats.active_loans);
      $("#totalAmount").text(formatCurrency(stats.total_amount));
      $("#monthlyCollection").text(formatCurrency(stats.total_monthly_payment));
    }
  } catch (error) {
    console.error("Failed to load statistics:", error);
  }
}

// Handle search
async function handleSearch() {
  const keyword = $("#searchInput").val();

  if (!keyword.trim()) {
    if (dataTable) {
      dataTable.search("").draw();
    }
    return;
  }

  try {
    const response = await LoanAPI.searchLoans(keyword);
    if (response.success) {
      const filteredLoans = response.data;
      if (dataTable) {
        // Clear DataTable and add filtered data
        dataTable.clear();
        filteredLoans.forEach((loan) => {
          dataTable.row.add([
            loan.id,
            `<strong>${escapeHtml(loan.borrower_name)}</strong>`,
            formatCurrency(loan.loan_amount),
            `${loan.interest_rate}%`,
            `${loan.loan_term} mo`,
            formatCurrency(loan.monthly_payment),
            formatCurrency(loan.total_paid || 0),
            formatCurrency(loan.remaining_balance || loan.loan_amount),
            `<span class="status-badge status-${loan.status}">${loan.status}</span>`,
            `<button class="btn btn-sm btn-info" onclick="viewLoan(${loan.id})"><i class="bi bi-eye"></i></button>
                         <button class="btn btn-sm btn-warning" onclick="editLoan(${loan.id})"><i class="bi bi-pencil"></i></button>
                         <button class="btn btn-sm btn-success" onclick="recordPayment(${loan.id})"><i class="bi bi-cash"></i></button>
                         <button class="btn btn-sm btn-danger" onclick="deleteLoan(${loan.id})"><i class="bi bi-trash"></i></button>`,
          ]);
        });
        dataTable.draw();
      }
    }
  } catch (error) {
    console.error("Search failed:", error);
  }
}

// Show add loan modal
function showAddLoanModal() {
  $("#modalTitle").text("Add New Loan");
  $("#loanForm")[0].reset();
  $("#loanId").val("");
  $("#startDate").val(new Date().toISOString().split("T")[0]);
  calculateMonthlyPreview();
  $("#loanModal").modal("show");
}

// Edit loan
async function editLoan(id) {
  try {
    const response = await LoanAPI.getLoan(id);
    if (response.success) {
      const loan = response.data;
      $("#modalTitle").text("Edit Loan");
      $("#loanId").val(loan.id);
      $("#borrowerName").val(loan.borrower_name);
      $("#loanAmount").val(loan.loan_amount);
      $("#interestRate").val(loan.interest_rate);
      $("#loanTerm").val(loan.loan_term);
      $("#startDate").val(loan.start_date);
      $("#status").val(loan.status);
      calculateMonthlyPreview();
      $("#loanModal").modal("show");
    }
  } catch (error) {
    showError("Failed to load loan details: " + error.message);
  }
}

// Handle loan form submit
async function handleLoanSubmit(e) {
  e.preventDefault();

  const loanData = {
    borrower_name: $("#borrowerName").val(),
    loan_amount: parseFloat($("#loanAmount").val()),
    interest_rate: parseFloat($("#interestRate").val()),
    loan_term: parseInt($("#loanTerm").val()),
    start_date: $("#startDate").val(),
    status: $("#status").val(),
  };

  const loanId = $("#loanId").val();

  try {
    let response;
    if (loanId) {
      response = await LoanAPI.updateLoan(loanId, loanData);
    } else {
      response = await LoanAPI.createLoan(loanData);
    }

    if (response.success) {
      $("#loanModal").modal("hide");
      await loadLoans();
      await loadStatistics();
      showSuccess(
        loanId ? "Loan updated successfully!" : "Loan created successfully!",
      );
    }
  } catch (error) {
    showError("Failed to save loan: " + error.message);
  }
}

// Delete loan
async function deleteLoan(id) {
  const confirmed = await showConfirm(
    "Are you sure?",
    "This action cannot be undone!",
  );
  if (!confirmed) return;

  try {
    const response = await LoanAPI.deleteLoan(id);
    if (response.success) {
      await loadLoans();
      await loadStatistics();
      showSuccess("Loan deleted successfully!");
    }
  } catch (error) {
    showError("Failed to delete loan: " + error.message);
  }
}

// Record payment
function recordPayment(loanId) {
  $("#paymentLoanId").val(loanId);
  $("#paymentAmount").val("");
  $("#paymentDate").val(new Date().toISOString().split("T")[0]);
  $("#paymentModal").modal("show");
}

// Handle payment form submit
async function handlePaymentSubmit(e) {
  e.preventDefault();

  const loanId = $("#paymentLoanId").val();
  const paymentData = {
    amount: parseFloat($("#paymentAmount").val()),
    payment_date: $("#paymentDate").val(),
  };

  try {
    const response = await LoanAPI.makePayment(loanId, paymentData);
    if (response.success) {
      $("#paymentModal").modal("hide");
      await loadLoans();
      await loadStatistics();
      showSuccess("Payment recorded successfully!");
    }
  } catch (error) {
    showError("Failed to record payment: " + error.message);
  }
}

// View loan details
async function viewLoan(id) {
  try {
    const response = await LoanAPI.getLoan(id);
    if (response.success) {
      const loan = response.data;
      const scheduleResponse = await LoanAPI.getPaymentSchedule(id);

      let scheduleHtml = "";
      if (scheduleResponse.success && scheduleResponse.data) {
        scheduleHtml = `
                    <h6 class="mt-4 mb-3">Payment Schedule</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered payment-schedule-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Month</th>
                                    <th>Payment</th>
                                    <th>Principal</th>
                                    <th>Interest</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${scheduleResponse.data
                                  .map(
                                    (payment) => `
                                    <tr>
                                        <td class="text-center">${payment.month}</td>
                                        <td class="text-end">${formatCurrency(payment.payment_amount)}</td>
                                        <td class="text-end">${formatCurrency(payment.principal)}</td>
                                        <td class="text-end">${formatCurrency(payment.interest)}</td>
                                        <td class="text-end">${formatCurrency(payment.remaining_balance)}</td>
                                    </tr>
                                `,
                                  )
                                  .join("")}
                            </tbody>
                        </table>
                    </div>
                `;
      }

      const paymentsHtml =
        loan.payments && loan.payments.length > 0
          ? `
                <h6 class="mt-4 mb-3">Payment History</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Remaining Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${loan.payments
                              .map(
                                (payment) => `
                                <tr>
                                    <td>${payment.payment_date}</td>
                                    <td>${formatCurrency(payment.payment_amount)}</td>
                                    <td>${formatCurrency(payment.remaining_balance)}</td>
                                </tr>
                            `,
                              )
                              .join("")}
                        </tbody>
                    </table>
                </div>
            `
          : '<p class="text-muted mt-3">No payments recorded yet.</p>';

      const content = `
                <div class="loan-details-card card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><i class="bi bi-person"></i> Borrower:</strong> ${escapeHtml(loan.borrower_name)}</p>
                                <p><strong><i class="bi bi-cash"></i> Loan Amount:</strong> ${formatCurrency(loan.loan_amount)}</p>
                                <p><strong><i class="bi bi-percent"></i> Interest Rate:</strong> ${loan.interest_rate}%</p>
                                <p><strong><i class="bi bi-calendar"></i> Term:</strong> ${loan.loan_term} months</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><i class="bi bi-currency-dollar"></i> Monthly Payment:</strong> ${formatCurrency(loan.monthly_payment)}</p>
                                <p><strong><i class="bi bi-piggy-bank"></i> Total Paid:</strong> ${formatCurrency(loan.total_paid || 0)}</p>
                                <p><strong><i class="bi bi-graph-down"></i> Remaining Balance:</strong> ${formatCurrency(loan.remaining_balance || loan.loan_amount)}</p>
                                <p><strong><i class="bi bi-tag"></i> Status:</strong> <span class="status-badge status-${loan.status}">${loan.status}</span></p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <p><strong><i class="bi bi-calendar-check"></i> Start Date:</strong> ${loan.start_date}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><i class="bi bi-calendar-x"></i> Due Date:</strong> ${loan.due_date}</p>
                            </div>
                        </div>
                    </div>
                </div>
                ${paymentsHtml}
                ${scheduleHtml}
            `;

      $("#loanDetailsContent").html(content);
      $("#loanDetailsModal").modal("show");
    }
  } catch (error) {
    showError("Failed to load loan details: " + error.message);
  }
}

// Show all payments
async function showAllPayments() {
  try {
    const response = await LoanAPI.getAllPayments();
    if (response.success) {
      const payments = response.data.data;

      let content = `
                <div class="table-responsive">
                    <table class="table table-hover" id="paymentsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Loan ID</th>
                                <th>Borrower</th>
                                <th>Payment Amount</th>
                                <th>Remaining Balance</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

      payments.forEach((payment) => {
        content += `
                    <tr>
                        <td>${payment.payment_date}</td>
                        <td>${payment.loan_id}</td>
                        <td>${escapeHtml(payment.borrower_name || "N/A")}</td>
                        <td class="text-success">${formatCurrency(payment.payment_amount)}</td>
                        <td>${formatCurrency(payment.remaining_balance)}</td>
                    </tr>
                `;
      });

      content += `
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle"></i> Total Payments: ${payments.length}
                </div>
            `;

      $("#allPaymentsContent").html(content);
      $("#allPaymentsModal").modal("show");

      // Initialize DataTable for payments
      setTimeout(() => {
        $("#paymentsTable").DataTable({
          pageLength: 10,
          responsive: true,
          order: [[0, "desc"]],
        });
      }, 100);
    }
  } catch (error) {
    showError("Failed to load payments: " + error.message);
  }
}

// Export to CSV
async function exportToCSV() {
  try {
    const response = await LoanAPI.getLoans();
    if (response.success) {
      const loans = response.data.loans;

      // Prepare CSV data
      const csvData = loans.map((loan) => ({
        "Loan ID": loan.id,
        "Borrower Name": loan.borrower_name,
        "Loan Amount": loan.loan_amount,
        "Interest Rate": loan.interest_rate,
        "Loan Term (months)": loan.loan_term,
        "Monthly Payment": loan.monthly_payment,
        "Total Paid": loan.total_paid || 0,
        "Remaining Balance": loan.remaining_balance || loan.loan_amount,
        Status: loan.status,
        "Start Date": loan.start_date,
        "Due Date": loan.due_date,
      }));

      // Convert to CSV
      const headers = Object.keys(csvData[0]);
      const csvRows = [];
      csvRows.push(headers.join(","));

      for (const row of csvData) {
        const values = headers.map((header) => {
          const val = row[header];
          return `"${String(val).replace(/"/g, '""')}"`;
        });
        csvRows.push(values.join(","));
      }

      const csvString = csvRows.join("\n");
      const blob = new Blob([csvString], { type: "text/csv" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `loans_export_${new Date().toISOString().split("T")[0]}.csv`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);

      showSuccess("Export completed successfully!");
    }
  } catch (error) {
    showError("Failed to export data: " + error.message);
  }
}

// Helper functions
function formatCurrency(amount) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: 2,
  }).format(amount);
}

function escapeHtml(text) {
  if (!text) return "";
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

function calculateMonthlyPreview() {
  const amount = parseFloat($("#loanAmount").val()) || 0;
  const rate = parseFloat($("#interestRate").val()) || 0;
  const term = parseInt($("#loanTerm").val()) || 0;

  if (amount > 0 && term > 0) {
    const monthlyRate = rate / 100 / 12;
    let payment;
    if (monthlyRate === 0) {
      payment = amount / term;
    } else {
      payment =
        (amount * (monthlyRate * Math.pow(1 + monthlyRate, term))) /
        (Math.pow(1 + monthlyRate, term) - 1);
    }
    $("#monthlyPaymentPreview").text(payment.toFixed(2));
  } else {
    $("#monthlyPaymentPreview").text("0.00");
  }
}

function showSuccess(message) {
  Swal.fire({
    icon: "success",
    title: "Success!",
    text: message,
    timer: 2000,
    showConfirmButton: false,
  });
}

function showError(message) {
  Swal.fire({
    icon: "error",
    title: "Error!",
    text: message,
    confirmButtonColor: "#dc3545",
  });
}

async function showConfirm(title, text) {
  const result = await Swal.fire({
    title: title,
    text: text,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#dc3545",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, delete it!",
  });
  return result.isConfirmed;
}

// Load dashboard (refresh data)
function loadDashboard() {
  loadLoans();
  loadStatistics();
  showSuccess("Dashboard refreshed!");
}
