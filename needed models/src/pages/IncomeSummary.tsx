
import React, { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { CalendarIcon } from "lucide-react";
import { format } from "date-fns";
import { cn } from "@/lib/utils";
import { Calendar } from "@/components/ui/calendar";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

interface IncomeAccount {
  acct_id: string;
  acct_code: string;
  acct_desc: string;
  acct_table_name: string;
}

interface IncomeSummary {
  totalAmount: number;
  transactions: IncomeTransaction[];
}

interface IncomeTransaction {
  id: string;
  date: string;
  description: string;
  amount: number;
  receipt_no?: string;
}

const IncomeSummary: React.FC = () => {
  const [accounts, setAccounts] = useState<IncomeAccount[]>([]);
  const [selectedAccount, setSelectedAccount] = useState<string>("");
  const [startDate, setStartDate] = useState<Date | undefined>(new Date());
  const [endDate, setEndDate] = useState<Date | undefined>(new Date());
  const [summaryData, setSummaryData] = useState<IncomeSummary | null>(null);
  const [loading, setLoading] = useState<boolean>(false);
  const navigate = useNavigate();
  
  useEffect(() => {
    // Fetch income line accounts
    fetchAccounts();
  }, []);
  
  const fetchAccounts = async () => {
    try {
      // Simulate fetching accounts for now
      // In real implementation, this would be an API call to get income line accounts
      setTimeout(() => {
        const mockAccounts: IncomeAccount[] = [
          { acct_id: "1", acct_code: "401", acct_desc: "Shop Rent", acct_table_name: "income_shop_rent" },
          { acct_id: "2", acct_code: "402", acct_desc: "Service Charge", acct_table_name: "income_service_charge" },
          { acct_id: "3", acct_code: "403", acct_desc: "Electricity Bills", acct_table_name: "income_electricity_bills" },
          { acct_id: "4", acct_code: "404", acct_desc: "Parking Fees", acct_table_name: "income_parking_fees" }
        ];
        setAccounts(mockAccounts);
      }, 500);
    } catch (error) {
      console.error("Error fetching accounts:", error);
    }
  };
  
  const fetchIncomeSummary = async () => {
    if (!selectedAccount || !startDate || !endDate) {
      alert("Please select an income line and date range");
      return;
    }
    
    setLoading(true);
    
    try {
      // In real implementation, this would be an API call with the selectedAccount, startDate, endDate
      // For now, we'll simulate the API response
      setTimeout(() => {
        const account = accounts.find(acc => acc.acct_code === selectedAccount);
        
        const mockTransactions: IncomeTransaction[] = [];
        let totalAmount = 0;
        
        // Generate random transactions for the selected date range
        const currentDate = new Date(startDate);
        const end = new Date(endDate);
        
        while (currentDate <= end) {
          if (Math.random() > 0.5) { // Not every day has transactions
            const amount = Math.floor(Math.random() * 100000) + 5000;
            totalAmount += amount;
            
            mockTransactions.push({
              id: `tr-${mockTransactions.length + 1}`,
              date: format(currentDate, "yyyy-MM-dd"),
              description: `Payment for ${account?.acct_desc}`,
              amount: amount,
              receipt_no: `REC-${Math.floor(Math.random() * 10000)}`
            });
          }
          
          // Move to next day
          currentDate.setDate(currentDate.getDate() + 1);
        }
        
        setSummaryData({
          totalAmount,
          transactions: mockTransactions
        });
        
        setLoading(false);
      }, 1000);
    } catch (error) {
      console.error("Error fetching income summary:", error);
      setLoading(false);
    }
  };
  
  const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat("en-NG", {
      style: "currency",
      currency: "NGN",
      minimumFractionDigits: 2
    }).format(amount);
  };
  
  return (
    <div className="container mx-auto p-4">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Income Summary</h1>
        <Button onClick={() => navigate("/")}>Back to Dashboard</Button>
      </div>
      
      <Card className="mb-6">
        <CardHeader>
          <CardTitle>Filter Options</CardTitle>
          <CardDescription>
            Select an income line and date range to view the summary
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium mb-1">Income Line</label>
              <Select value={selectedAccount} onValueChange={setSelectedAccount}>
                <SelectTrigger>
                  <SelectValue placeholder="Select Income Line" />
                </SelectTrigger>
                <SelectContent>
                  {accounts.map((account) => (
                    <SelectItem key={account.acct_id} value={account.acct_code}>
                      {account.acct_desc} ({account.acct_code})
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            
            <div>
              <label className="block text-sm font-medium mb-1">Start Date</label>
              <Popover>
                <PopoverTrigger asChild>
                  <Button
                    variant="outline"
                    className={cn(
                      "w-full justify-start text-left font-normal",
                      !startDate && "text-muted-foreground"
                    )}
                  >
                    <CalendarIcon className="mr-2 h-4 w-4" />
                    {startDate ? format(startDate, "PPP") : <span>Pick a date</span>}
                  </Button>
                </PopoverTrigger>
                <PopoverContent className="w-auto p-0" align="start">
                  <Calendar
                    mode="single"
                    selected={startDate}
                    onSelect={setStartDate}
                    initialFocus
                    className={cn("p-3 pointer-events-auto")}
                  />
                </PopoverContent>
              </Popover>
            </div>
            
            <div>
              <label className="block text-sm font-medium mb-1">End Date</label>
              <Popover>
                <PopoverTrigger asChild>
                  <Button
                    variant="outline"
                    className={cn(
                      "w-full justify-start text-left font-normal",
                      !endDate && "text-muted-foreground"
                    )}
                  >
                    <CalendarIcon className="mr-2 h-4 w-4" />
                    {endDate ? format(endDate, "PPP") : <span>Pick a date</span>}
                  </Button>
                </PopoverTrigger>
                <PopoverContent className="w-auto p-0" align="start">
                  <Calendar
                    mode="single"
                    selected={endDate}
                    onSelect={setEndDate}
                    initialFocus
                    className={cn("p-3 pointer-events-auto")}
                  />
                </PopoverContent>
              </Popover>
            </div>
          </div>
        </CardContent>
        <CardFooter>
          <Button onClick={fetchIncomeSummary} disabled={loading}>
            {loading ? "Loading..." : "Generate Summary"}
          </Button>
        </CardFooter>
      </Card>
      
      {summaryData && (
        <>
          <Card className="mb-6">
            <CardHeader>
              <CardTitle>
                Summary for {accounts.find(acc => acc.acct_code === selectedAccount)?.acct_desc}
              </CardTitle>
              <CardDescription>
                From {startDate && format(startDate, "PPP")} to {endDate && format(endDate, "PPP")}
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-center p-4 bg-blue-50 rounded-lg">
                Total Income: {formatCurrency(summaryData.totalAmount)}
              </div>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader>
              <CardTitle>Transaction Details</CardTitle>
              <CardDescription>
                Showing {summaryData.transactions.length} transactions
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Date</TableHead>
                    <TableHead>Description</TableHead>
                    <TableHead>Receipt No.</TableHead>
                    <TableHead className="text-right">Amount</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {summaryData.transactions.map((transaction) => (
                    <TableRow key={transaction.id}>
                      <TableCell>{transaction.date}</TableCell>
                      <TableCell>{transaction.description}</TableCell>
                      <TableCell>{transaction.receipt_no}</TableCell>
                      <TableCell className="text-right font-medium">
                        {formatCurrency(transaction.amount)}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </>
      )}
    </div>
  );
};

export default IncomeSummary;
