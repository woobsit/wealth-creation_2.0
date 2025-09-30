
import React from "react";
import { SidebarProvider, SidebarTrigger } from "@/components/ui/sidebar";
import { AppSidebar } from "../app-sidebar";

type Props = {
  children: React.ReactNode;
};

const MainLayout = ({ children }: Props) => {
  return (
    <SidebarProvider>
      <div className="flex min-h-screen w-full">
        <AppSidebar />
        <div className="flex-1 flex flex-col bg-background">
          {/* Top bar with profile and logout */}
          <div className="flex items-center justify-end px-6 py-4 border-b">
            {/* Place your profile and logout (top right) here, possibly as a separate ProfileMenu component */}
            {/* Example: <ProfileMenu /> */}
          </div>
          <main className="p-6 flex-1">{children}</main>
        </div>
        <SidebarTrigger className="fixed top-4 left-4 md:hidden z-50" />
      </div>
    </SidebarProvider>
  );
};

export default MainLayout;
