
// Utility functions for power consumption calculations

/**
 * Calculate consumption based on meter readings
 */
export const calculateConsumption = (previousReading: number, presentReading: number): number => {
  return Math.abs(presentReading - previousReading);
};

/**
 * Calculate cost based on consumption and tariff
 */
export const calculateCost = (consumption: number, tariff: number): number => {
  return consumption * tariff;
};

/**
 * Calculate VAT amount (7.5%)
 */
export const calculateVAT = (cost: number): number => {
  const VAT_RATE = 0.075; // 7.5%
  return cost * VAT_RATE;
};

/**
 * Calculate total payable amount
 */
export const calculateTotalPayable = (cost: number, vatAmount: number, previousOutstanding: number): number => {
  return cost + vatAmount + previousOutstanding;
};

/**
 * Calculate current balance
 */
export const calculateBalance = (totalPayable: number, totalPaid: number): number => {
  return totalPayable - totalPaid;
};

/**
 * Format currency amount
 */
export const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
  }).format(amount);
};

/**
 * Format date to YYYY-MM-DD
 */
export const formatDate = (date: Date): string => {
  return date.toISOString().split('T')[0];
};

/**
 * Generate month year string (e.g., "March, 2024")
 */
export const generateMonthYear = (date: Date = new Date()): string => {
  const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];
  
  // Get previous month (for billing)
  const prevMonth = date.getMonth() === 0 ? 11 : date.getMonth() - 1;
  const year = date.getMonth() === 0 ? date.getFullYear() - 1 : date.getFullYear();
  
  return `${months[prevMonth]}, ${year}`;
};
