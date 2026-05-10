1.  Simple Interest
    Interest = P × R × T
    Total Payment = P + Interest
    P = Principal (loan amount)
    R = Interest rate (per year, decimal form e.g. 0.05)
    T = Time (in years)

2.  Compound Interest
    A = P (1 + R / n)^(nT)
    Interest = A - P

        A = Total amount

    n = number of times compounded per year

3.  Flat Rate Interest
    Interest = P × R × T
    Monthly Payment = (P + Interest) / Number of Months
    ✔ Easy to implement
    ❗ Higher effective rate
4.  Reducing Balance (Diminishing)
    👉 Interest decreases as balance decreases  
     Interest = Remaining Balance × (R / periods)
    Principal Payment = Fixed Payment - Interest
    Most recommended for fairness
5.  Add-On Interest
    👉 Interest is precomputed and added upfront
    Interest = P × R × T
    Total Loan = P + Interest
    Monthly Payment = Total Loan / Months

✔ Very common in lending systems
❗ Similar to flat rate but presented differently

6. Amortized Loan (EMI Method)

👉 Fixed monthly payments

EMI = P × r × (1 + r)^n / ((1 + r)^n - 1)

r = monthly interest rate (R / 12)
n = total number of payments

✔ Used in:

Car loans
Housing loans

7. Fixed Interest Rate

👉 Rate does not change

Formula:

(Same as chosen method, just constant R)

8. Variable Interest Rate

👉 Rate changes over time

Formula:

(No single formula)
👉 Recalculate per period:

Interest = Balance × Current Rate

🔥 Recommended for Your System (Practical Setup)

For a cooperative loan system, implement these first:

✅ Core Types:
Simple Interest
Flat Rate
Reducing Balance
Amortization (EMI)

'simple',
'compound',
'flat',
'reducing',
'add_on',
'amortized'
)
