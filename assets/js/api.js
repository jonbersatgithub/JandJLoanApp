// API Configuration
const API_BASE_URL = "/JandJLoanApp/api";

class LoanAPI {
  static async request(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint}`;
    const defaultOptions = {
      headers: {
        "Content-Type": "application/json",
      },
    };

    const mergedOptions = { ...defaultOptions, ...options };

    if (mergedOptions.body && typeof mergedOptions.body === "object") {
      mergedOptions.body = JSON.stringify(mergedOptions.body);
    }

    try {
      const response = await fetch(url, mergedOptions);
      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || "API request failed");
      }

      return data;
    } catch (error) {
      console.error("API Error:", error);
      throw error;
    }
  }

  static getLoans() {
    return this.request("/loans");
  }

  static getLoan(id) {
    return this.request(`/loans/${id}`);
  }

  static createLoan(loanData) {
    // alert(loanData);
    // return;
    return this.request("/loans", {
      method: "POST",
      body: loanData,
    });
  }

  static updateLoan(id, loanData) {
    return this.request(`/loans/${id}`, {
      method: "PUT",
      body: loanData,
    });
  }

  static deleteLoan(id) {
    return this.request(`/loans/${id}`, {
      method: "DELETE",
    });
  }

  static searchLoans(keyword) {
    return this.request(`/loans/search?q=${encodeURIComponent(keyword)}`);
  }

  static getStatistics() {
    return this.request("/statistics");
  }

  static makePayment(loanId, paymentData) {
    return this.request(`/loans/${loanId}/payments`, {
      method: "POST",
      body: paymentData,
    });
  }

  static getPaymentSchedule(loanId) {
    return this.request(`/loans/${loanId}/schedule`);
  }

  static getAllPayments() {
    return this.request("/payments?limit=100");
  }
}
