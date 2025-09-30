
// This service will handle API calls related to income data

/**
 * Fetches all income line accounts
 */
export const fetchIncomeLineAccounts = async () => {
  try {
    const response = await fetch('/api/accounts/income-lines');
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    return await response.json();
  } catch (error) {
    console.error("Error fetching income line accounts:", error);
    throw error;
  }
};

/**
 * Fetches income summary for a specific income line and date range
 */
export const fetchIncomeSummary = async (
  accountCode: string,
  startDate: string,
  endDate: string
) => {
  try {
    const url = `/api/income-summary?account=${accountCode}&start_date=${startDate}&end_date=${endDate}`;
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    return await response.json();
  } catch (error) {
    console.error("Error fetching income summary:", error);
    throw error;
  }
};
