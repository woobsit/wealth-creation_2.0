
import { PowerConsumption, PowerConsumptionHistory } from "../types/powerConsumption";

// Base URL for API calls
const API_BASE_URL = 'http://localhost/income_erp/api';

// Function to fetch customer details by shop number
export const getCustomerByShopNo = async (shopNo: string): Promise<PowerConsumption | null> => {
  try {
    const response = await fetch(`${API_BASE_URL}/get_customer_power.php?shop_no=${shopNo}`);
    const data = await response.json();
    
    if (data.success) {
      return data.customer;
    }
    return null;
  } catch (error) {
    console.error("Error fetching customer:", error);
    throw new Error("Failed to fetch customer data");
  }
};

// Function to save new meter reading
export const saveNewMeterReading = async (readingData: PowerConsumption): Promise<{ success: boolean; message: string; data?: PowerConsumption }> => {
  try {
    const response = await fetch(`${API_BASE_URL}/save_power_reading.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(readingData),
    });
    
    return await response.json();
  } catch (error) {
    console.error("Error saving meter reading:", error);
    return { success: false, message: "Failed to save meter reading. Please try again." };
  }
};

// Function to get consumption history for a customer
export const getConsumptionHistory = async (shopId: string): Promise<PowerConsumptionHistory[]> => {
  try {
    const response = await fetch(`${API_BASE_URL}/get_power_history.php?shop_id=${shopId}`);
    const data = await response.json();
    
    if (data.success) {
      return data.history;
    }
    return [];
  } catch (error) {
    console.error("Error fetching consumption history:", error);
    return [];
  }
};
