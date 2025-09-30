// Configuration
const API_BASE_URL = 'http://localhost/income_erp/api';
const VAT_RATE = 0.075; // 7.5%

// Utility functions
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
};

const calculateConsumption = (previousReading, presentReading) => {
    return Math.abs(presentReading - previousReading);
};

const calculateCost = (consumption, tariff) => {
    return consumption * tariff;
};

// Function to get the previous month and year
function getPreviousMonthYear() {
    const currentDate = new Date();
    // Get the previous month
    const previousMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1);
    
    const months = [
        'January', 'February', 'March', 'April', 'May', 'June', 
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    
    return `${months[previousMonth.getMonth()]}, ${previousMonth.getFullYear()}`;
}

// On page load, set the current month
document.addEventListener('DOMContentLoaded', function() {
    const currentMonthElements = document.querySelectorAll('#currentMonth');
    const billingMonth = getPreviousMonthYear();
    
    currentMonthElements.forEach(element => {
        element.textContent = billingMonth;
        element.value = billingMonth;
    });
});

// Simulated history data for the last 6 months
const generateHistoryData = () => {
    const months = [];
    const currentDate = new Date();
    
    for (let i = 6; i >= 1; i--) {
        const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
        const monthName = date.toLocaleString('default', { month: 'long' });
        const year = date.getFullYear();
        
        // Generate random but realistic-looking data
        const consumption = (200 + Math.random() * 150).toFixed(2);
        const cost = (consumption * 280).toFixed(2);
        const totalPayable = (parseFloat(cost) * 1.075).toFixed(2); // Adding 7.5% VAT
        const paid = (Math.random() > 0.3 ? parseFloat(totalPayable) : parseFloat(totalPayable) * 0.95).toFixed(2);
        const balance = (parseFloat(totalPayable) - parseFloat(paid)).toFixed(2);
        
        months.push({
            period: `${monthName} ${year}`,
            consumption: parseFloat(consumption),
            cost: parseFloat(cost),
            totalPayable: parseFloat(totalPayable),
            paid: parseFloat(paid),
            balance: parseFloat(balance)
        });
    }
    
    return months;
};

// Function to display consumption history
function displayConsumptionHistory() {
    const historyCards = document.getElementById('historyCards');
    historyCards.innerHTML = ''; // Clear existing content
    
    const historyData = generateHistoryData();
    
    historyData.forEach(month => {
        const card = document.createElement('div');
        card.className = 'history-card';
        
        // Create card content
        card.innerHTML = `
            <div class="history-card-header">${month.period}</div>
            <div class="history-card-body">
                <div class="history-card-row">
                    <span class="history-card-label">Consumption:</span>
                    <span class="history-card-value">${month.consumption.toFixed(2)}</span>
                </div>
                <div class="history-card-row">
                    <span class="history-card-label">Cost:</span>
                    <span class="history-card-value">₦ ${formatCurrency(month.cost)}</span>
                </div>
                <div class="history-card-row">
                    <span class="history-card-label">Total Payable:</span>
                    <span class="history-card-value">₦ ${formatCurrency(month.totalPayable)}</span>
                </div>
                <div class="history-card-row">
                    <span class="history-card-label">Paid:</span>
                    <span class="history-card-value">₦ ${formatCurrency(month.paid)}</span>
                </div>
                <div class="history-card-row">
                    <span class="history-card-label">Balance:</span>
                    <span class="history-card-value ${month.balance < 0 ? 'negative' : 'positive'}">
                        ₦ ${formatCurrency(month.balance)}
                    </span>
                </div>
            </div>
        `;
        
        historyCards.appendChild(card);
    });
    
    document.getElementById('consumptionHistory').classList.remove('hidden');
}

// API functions
async function searchCustomer() {
    const shopNo = document.getElementById('shopNoSearch').value;
    if (!shopNo) {
        alert('Please enter a shop number');
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/get_customer_power.php?shop_no=${shopNo}`);
        const data = await response.json();

        if (data.success) {
            displayCustomerDetails(data.customer);
            displayConsumptionHistory(); // Add this line to show history when customer is found
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to fetch customer details');
    }
}

async function fetchConsumptionHistory(shopId) {
    try {
        const response = await fetch(`${API_BASE_URL}/get_power_history.php?shop_id=${shopId}`);
        const data = await response.json();

        if (data.success) {
            displayConsumptionHistory(data.history);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Display functions
function displayCustomerDetails(customer) {
    document.getElementById('customerDetails').classList.remove('hidden');
    document.getElementById('readingForm').classList.remove('hidden');
    
    document.getElementById('shopNo').textContent = customer.shop_no;
    document.getElementById('customerName').textContent = customer.customer_name;
    document.getElementById('meterNo').textContent = customer.meter_no;
    document.getElementById('currentMonth').textContent = customer.current_month;
    document.getElementById('previousReading').value = customer.present_reading;
    document.getElementById('previousOutstanding').textContent = formatCurrency(customer.previous_outstanding);
    
    // Store customer data for later use
    window.currentCustomer = customer;
}

// Calculation functions
function calculateBill() {
    const customer = window.currentCustomer;
    if (!customer) {
        alert('Please search for a customer first');
        return;
    }

    const previousReading = parseFloat(document.getElementById('previousReading').value);
    const presentReading = parseFloat(document.getElementById('presentReading').value);
    const previousOutstanding = parseFloat(customer.previous_outstanding) || 0;

    if (isNaN(presentReading)) {
        alert('Please enter a valid present reading');
        return;
    }

    const consumption = calculateConsumption(previousReading, presentReading);
    const cost = calculateCost(consumption, customer.tariff);
    const vatCost = cost * VAT_RATE;
    const totalPayable = cost + vatCost + previousOutstanding;

    // Display calculations
    document.getElementById('consumption').textContent = consumption.toFixed(2);
    document.getElementById('cost').textContent = formatCurrency(cost);
    document.getElementById('vatCost').textContent = formatCurrency(vatCost);
    document.getElementById('totalPayable').textContent = formatCurrency(totalPayable);

    document.getElementById('billSummary').classList.remove('hidden');
}

async function saveBill() {
    const customer = window.currentCustomer;
    if (!customer) {
        alert('Please search for a customer first');
        return;
    }

    const presentReading = parseFloat(document.getElementById('presentReading').value);
    const dateOfReading = document.getElementById('dateOfReading').value;

    if (!dateOfReading) {
        alert('Please enter the date of reading');
        return;
    }

    const consumption = parseFloat(document.getElementById('consumption').textContent);
    const cost = parseFloat(document.getElementById('cost').textContent.replace(/[^0-9.-]+/g, ''));
    const vatCost = parseFloat(document.getElementById('vatCost').textContent.replace(/[^0-9.-]+/g, ''));
    const totalPayable = parseFloat(document.getElementById('totalPayable').textContent.replace(/[^0-9.-]+/g, ''));

    const data = {
        ...customer,
        present_reading: presentReading,
        date_of_reading: dateOfReading,
        consumption,
        cost,
        vat_on_cost: vatCost,
        total_payable: totalPayable
    };

    try {
        const response = await fetch(`${API_BASE_URL}/save_power_reading.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            alert('Bill saved successfully');
            // Refresh the page or clear the form
            location.reload();
        } else {
            alert(result.message || 'Failed to save bill');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to save bill');
    }
}
