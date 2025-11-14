
import React from "react";
import MainLayout from "../components/layout/MainLayout";

const PowerConsumptionPage = () => {
  return (
    <MainLayout>
      <div className="container mx-auto p-4">
        <h1 className="text-2xl font-bold mb-4">Power Consumption Management</h1>
        <p className="mb-4">
          This page redirects to the HTML-based Power Consumption Management system.
        </p>
        <div className="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
          <p>Redirecting to HTML version...</p>
        </div>
      </div>
    </MainLayout>
  );
};

// Redirect to HTML version on component mount
if (typeof window !== "undefined") {
  window.location.href = "/power_consumption.html";
}

export default PowerConsumptionPage;
