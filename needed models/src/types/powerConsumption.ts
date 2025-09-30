
export interface PowerConsumption {
  id?: number;
  power_id: string;
  shop_id: string;
  shop_no: string;
  customer_name: string;
  old_shop_no: string;
  old_customer_name: string;
  no_of_users: number;
  meter_no: string;
  meter_model: string;
  tariff: number;
  current_month: string;
  previous_outstanding: number;
  previous_reading: number;
  present_reading: number;
  consumption: number;
  cost: number;
  total_payable: number;
  total_paid: number;
  balance: number;
  date_of_reading: string;
  payment_due_date: string;
  update_status: string;
  update_timestamp: string;
  type_of_payment: string;
  staff_id: string;
  staff_name: string;
  updating_officer_id: string;
  updating_officer_name: string;
  unique_ref: string;
  print_status: string;
  print_date: string;
  billing_category: string;
  bill_status: string;
  vat_on_cost: number;
}

export interface PowerConsumptionHistory {
  id?: number;
  pid: number;
  power_id: string;
  shop_id: string;
  shop_no: string;
  customer_name: string;
  current_month: string;
  previous_reading: number;
  present_reading: number;
  consumption: number;
  cost: number;
  date_of_reading: string;
}
