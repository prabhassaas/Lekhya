import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'Lekhya — GST ERP',
  description: 'AI-enabled GST-compliant accounting ERP for India',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
