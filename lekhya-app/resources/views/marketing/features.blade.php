@extends('layouts.marketing')
@section('title', 'Features — Lekhya GST ERP')
@section('meta-desc', 'Explore all Lekhya features: double-entry accounting, GST compliance, AI invoice extraction, Seedha Bill connector, e-invoice, GSTR filing, and more.')

@section('content')

{{-- Hero --}}
<section class="bg-gradient-to-br from-navy-600 to-navy-900 text-white py-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <div class="inline-flex items-center px-3 py-1 bg-blue-500 bg-opacity-20 border border-blue-400 border-opacity-30 rounded-full text-blue-200 text-sm mb-6">
      <i class="fa fa-sparkles mr-2"></i> Everything you need · Nothing you don't
    </div>
    <h1 class="text-4xl sm:text-5xl font-bold mb-6">Lekhya Features</h1>
    <p class="text-xl text-gray-300 max-w-2xl mx-auto mb-10">
      A complete GST-compliant ERP built for Indian businesses — from solo consultants to CA firms managing dozens of clients.
    </p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-3 bg-blue-500 hover:bg-blue-400 text-white font-semibold rounded-xl transition">
        Start Free Trial <i class="fa fa-arrow-right ml-2"></i>
      </a>
      <a href="{{ route('marketing.pricing') }}" class="inline-flex items-center justify-center px-6 py-3 border border-white border-opacity-30 text-white hover:bg-white hover:bg-opacity-10 font-medium rounded-xl transition">
        View Pricing
      </a>
    </div>
  </div>
</section>

{{-- Feature nav --}}
<div class="sticky top-16 z-30 bg-white border-b border-gray-100 shadow-sm">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex overflow-x-auto gap-1 py-2 no-scrollbar">
      @foreach([
        ['#accounting','fa-book','Accounting'],
        ['#gst','fa-file-invoice','GST'],
        ['#ai','fa-robot','AI'],
        ['#connector','fa-link','Seedha Bill'],
        ['#banking','fa-university','Banking'],
        ['#pramaan','fa-briefcase','Pramaan CA'],
        ['#security','fa-shield-halved','Security'],
      ] as [$anchor,$icon,$label])
      <a href="{{ $anchor }}" class="flex-shrink-0 flex items-center space-x-1.5 px-4 py-2 text-sm font-medium text-gray-600 hover:text-navy-600 hover:bg-navy-50 rounded-lg transition whitespace-nowrap">
        <i class="fa {{ $icon }} text-xs"></i><span>{{ $label }}</span>
      </a>
      @endforeach
    </div>
  </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-28">

  {{-- ── ACCOUNTING ───────────────────────────────────────── --}}
  <section id="accounting">
    <div class="flex items-center space-x-3 mb-3">
      <div class="w-10 h-10 bg-navy-50 rounded-xl flex items-center justify-center">
        <i class="fa fa-book text-navy-600"></i>
      </div>
      <span class="text-sm font-semibold text-navy-600 uppercase tracking-wider">Accounting Core</span>
    </div>
    <h2 class="text-3xl font-bold text-gray-900 mb-4">Double-entry ledger, done right</h2>
    <p class="text-lg text-gray-600 mb-12 max-w-2xl">Every rupee tracked with proper debit/credit entries. No shortcuts. Posted journals are permanent — corrections go through proper reversals, just like your CA would want.</p>

    <div class="grid md:grid-cols-3 gap-6">
      @foreach([
        ['fa-sitemap','Chart of Accounts','Standard Indian GST chart pre-loaded. Customise account heads, add sub-accounts, set opening balances. Works out of the box on day 1.'],
        ['fa-scale-balanced','Double-Entry Journal Engine','Every transaction posts balanced debit and credit entries automatically. The engine rejects any unbalanced entry before it touches the ledger.'],
        ['fa-file-invoice-dollar','GST Invoices','Raise sales invoices with automatic CGST/SGST (intra-state) or IGST (inter-state) calculation. PDF generation built-in.'],
        ['fa-receipt','Purchase & Expense Entry','Log vendor bills and direct expenses with GST input credit captured correctly on every line item.'],
        ['fa-rotate-left','Reversing Entries','Made a mistake? Post a reversing journal — immutability preserved, audit trail clean. No direct edits, ever.'],
        ['fa-chart-line','P&L, Balance Sheet, Trial Balance','Real-time reports generated from live ledger data. Export to PDF or Excel. Date-range filtering with comparative periods.'],
        ['fa-users','Party Management','Maintain a ledger for every customer and vendor. Outstanding AR/AP aging reports at a glance.'],
        ['fa-calendar-alt','Multi Fiscal Year','Manage multiple financial years under one account. Year-end closing handled automatically.'],
        ['fa-file-import','Tally Import','Migrate from Tally ERP by uploading your XML export. Journals and chart of accounts transfer cleanly.'],
      ] as [$icon,$title,$desc])
      <div class="bg-white border border-gray-100 rounded-2xl p-6 hover:shadow-md transition">
        <div class="w-9 h-9 bg-navy-50 rounded-lg flex items-center justify-center mb-4">
          <i class="fa {{ $icon }} text-navy-600 text-sm"></i>
        </div>
        <h3 class="font-semibold text-gray-900 mb-2">{{ $title }}</h3>
        <p class="text-sm text-gray-600 leading-relaxed">{{ $desc }}</p>
      </div>
      @endforeach
    </div>
  </section>

  {{-- ── GST ───────────────────────────────────────────────── --}}
  <section id="gst">
    <div class="flex items-center space-x-3 mb-3">
      <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center">
        <i class="fa fa-file-invoice text-orange-600"></i>
      </div>
      <span class="text-sm font-semibold text-orange-600 uppercase tracking-wider">GST Compliance</span>
    </div>
    <h2 class="text-3xl font-bold text-gray-900 mb-4">Full GST stack — GSTR-1 to e-invoice</h2>
    <p class="text-lg text-gray-600 mb-12 max-w-2xl">File on time, every time. Lekhya handles HSN/SAC classification, rate calculation, e-invoice generation, and return preparation automatically.</p>

    <div class="grid md:grid-cols-2 gap-8 mb-8">
      <div class="bg-orange-50 border border-orange-200 rounded-2xl p-8">
        <h3 class="text-xl font-bold text-gray-900 mb-6">Returns & Filing</h3>
        <ul class="space-y-4">
          @foreach([
            ['GSTR-1','Outward supplies return — auto-populated from your sales invoices. B2B, B2C, HSN summary all computed.'],
            ['GSTR-3B','Monthly summary return — tax payable computed from your sales and purchase data.'],
            ['GSTR-2B Reconciliation','Match your purchase register against GSTR-2B auto-downloaded from the portal. Mismatches highlighted.'],
          ] as [$name,$desc])
          <li class="flex items-start space-x-3">
            <div class="w-6 h-6 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
              <i class="fa fa-check text-orange-600 text-xs"></i>
            </div>
            <div>
              <p class="font-semibold text-gray-900 text-sm">{{ $name }}</p>
              <p class="text-sm text-gray-600">{{ $desc }}</p>
            </div>
          </li>
          @endforeach
        </ul>
      </div>
      <div class="bg-blue-50 border border-blue-200 rounded-2xl p-8">
        <h3 class="text-xl font-bold text-gray-900 mb-6">e-Invoice & e-Way Bill</h3>
        <ul class="space-y-4">
          @foreach([
            ['e-Invoice (IRN)','One-click IRN generation via your chosen GSP. QR code embedded in PDF. Cancellation within 24 hours supported.'],
            ['e-Way Bill','Generate e-Way Bill for goods movement above ₹50,000. Auto-filled from invoice data — no re-entry.'],
            ['GSTIN Validation','Validate any GSTIN against the government database before raising an invoice. State code auto-detected.'],
          ] as [$name,$desc])
          <li class="flex items-start space-x-3">
            <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
              <i class="fa fa-check text-blue-600 text-xs"></i>
            </div>
            <div>
              <p class="font-semibold text-gray-900 text-sm">{{ $name }}</p>
              <p class="text-sm text-gray-600">{{ $desc }}</p>
            </div>
          </li>
          @endforeach
        </ul>
      </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
      @foreach([
        ['fa-tags','HSN / SAC Master','50,000+ HSN codes and SAC codes pre-loaded with their GST rates. Auto-suggest as you type the item description.'],
        ['fa-map-marker-alt','Intra vs Inter-State','CGST+SGST or IGST applied automatically based on supplier and buyer state codes. No manual rate selection.'],
        ['fa-plug','GSP Integration','Connect any GSP (Masters India, ClearTax, IRIS) via the GstGateway interface. Mock mode for testing — no real API calls needed.'],
      ] as [$icon,$title,$desc])
      <div class="bg-white border border-gray-100 rounded-2xl p-6 hover:shadow-md transition">
        <div class="w-9 h-9 bg-orange-50 rounded-lg flex items-center justify-center mb-4">
          <i class="fa {{ $icon }} text-orange-600 text-sm"></i>
        </div>
        <h3 class="font-semibold text-gray-900 mb-2">{{ $title }}</h3>
        <p class="text-sm text-gray-600 leading-relaxed">{{ $desc }}</p>
      </div>
      @endforeach
    </div>
  </section>

  {{-- ── AI ────────────────────────────────────────────────── --}}
  <section id="ai">
    <div class="flex items-center space-x-3 mb-3">
      <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center">
        <i class="fa fa-robot text-purple-600"></i>
      </div>
      <span class="text-sm font-semibold text-purple-600 uppercase tracking-wider">AI Tools</span>
    </div>
    <h2 class="text-3xl font-bold text-gray-900 mb-4">AI that proposes — you approve</h2>
    <p class="text-lg text-gray-600 mb-12 max-w-2xl">Lekhya's AI never writes to your ledger directly. It proposes, you review, you approve. Works offline with a local Ollama model or in the cloud with Claude.</p>

    <div class="grid md:grid-cols-2 gap-8 mb-8">
      <div class="relative bg-gradient-to-br from-purple-600 to-indigo-700 rounded-2xl p-8 text-white overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-8 -mt-8"></div>
        <i class="fa fa-robot text-4xl text-purple-300 mb-4"></i>
        <h3 class="text-xl font-bold mb-3">Works offline too</h3>
        <p class="text-purple-100 text-sm leading-relaxed">Connect a local Ollama model (llama3.2, mistral, etc.) and all AI features work without sending data to any cloud. Your books stay on your server.</p>
        <div class="mt-4 flex items-center space-x-2 text-sm">
          <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
          <span class="text-green-300 font-medium">Ollama · Claude · OpenAI</span>
        </div>
      </div>

      <div class="space-y-4">
        @foreach([
          ['fa-file-image','Invoice OCR & Extraction','Upload a PDF or photo of any invoice. AI reads it and fills in vendor, date, items, GST, amount — you just verify and post.'],
          ['fa-microphone','Natural Language Queries','Ask "What were my GST liabilities last quarter?" or "Show unpaid invoices above ₹1 lakh" in plain English or Hindi.'],
          ['fa-tags','Auto Account Coding','Paste an expense description and AI suggests the right account head from your Chart of Accounts — learns from your history.'],
          ['fa-triangle-exclamation','Anomaly Detection','AI flags journal entries that look unusual — duplicate amounts, rare vendors, entries outside working hours — for your review.'],
        ] as [$icon,$title,$desc])
        <div class="flex items-start space-x-4 bg-white border border-gray-100 rounded-xl p-4 hover:shadow-sm transition">
          <div class="w-9 h-9 bg-purple-50 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="fa {{ $icon }} text-purple-600 text-sm"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 text-sm mb-1">{{ $title }}</h3>
            <p class="text-xs text-gray-500 leading-relaxed">{{ $desc }}</p>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ── CONNECTOR ─────────────────────────────────────────── --}}
  <section id="connector">
    <div class="flex items-center space-x-3 mb-3">
      <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center">
        <i class="fa fa-link text-green-600"></i>
      </div>
      <span class="text-sm font-semibold text-green-600 uppercase tracking-wider">Seedha Bill Connector</span>
    </div>
    <h2 class="text-3xl font-bold text-gray-900 mb-4">Seedha Bill invoices → Lekhya ledger, automatically</h2>
    <p class="text-lg text-gray-600 mb-12 max-w-2xl">If you or your clients use Seedha Bill to raise invoices, those invoices flow into Lekhya for accounting — without any copy-paste or re-entry.</p>

    <div class="grid md:grid-cols-2 gap-8">
      <div class="bg-green-50 border-2 border-green-300 rounded-2xl p-8">
        <div class="flex items-center space-x-2 mb-4">
          <span class="w-7 h-7 bg-green-600 text-white rounded-full flex items-center justify-center text-sm font-bold">A</span>
          <h3 class="text-xl font-bold text-gray-900">Same Prabhas Account</h3>
          <span class="ml-auto text-xs bg-green-200 text-green-800 px-2 py-0.5 rounded-full font-medium">Easiest</span>
        </div>
        <p class="text-gray-600 text-sm mb-5">You use both Seedha Bill and Lekhya with the same login? One toggle and you're done. Invoices appear in Lekhya automatically.</p>
        <ul class="space-y-2 text-sm text-gray-700">
          <li class="flex items-center space-x-2"><i class="fa fa-check text-green-600"></i><span>One-toggle setup in Seedha Bill settings</span></li>
          <li class="flex items-center space-x-2"><i class="fa fa-check text-green-600"></i><span>No passwords or tokens to manage</span></li>
          <li class="flex items-center space-x-2"><i class="fa fa-check text-green-600"></i><span>Bundle discount on your subscription</span></li>
        </ul>
      </div>
      <div class="bg-purple-50 border-2 border-purple-300 rounded-2xl p-8">
        <div class="flex items-center space-x-2 mb-4">
          <span class="w-7 h-7 bg-purple-600 text-white rounded-full flex items-center justify-center text-sm font-bold">B</span>
          <h3 class="text-xl font-bold text-gray-900">Client uses Seedha Bill</h3>
        </div>
        <p class="text-gray-600 text-sm mb-5">Your client raises invoices in Seedha Bill, you handle their books in Lekhya. A simple connection code links them — your client controls the access.</p>
        <ul class="space-y-2 text-sm text-gray-700">
          <li class="flex items-center space-x-2"><i class="fa fa-check text-purple-600"></i><span>You generate a connection code in Lekhya</span></li>
          <li class="flex items-center space-x-2"><i class="fa fa-check text-purple-600"></i><span>Client pastes it in their Seedha Bill app</span></li>
          <li class="flex items-center space-x-2"><i class="fa fa-check text-purple-600"></i><span>Client can revoke access any time</span></li>
        </ul>
      </div>
    </div>

    <div class="mt-6 text-center">
      <a href="{{ route('marketing.connector') }}" class="text-navy-600 font-medium text-sm hover:underline">
        Learn more about the connector <i class="fa fa-arrow-right ml-1"></i>
      </a>
    </div>
  </section>

  {{-- ── BANKING ───────────────────────────────────────────── --}}
  <section id="banking">
    <div class="flex items-center space-x-3 mb-3">
      <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
        <i class="fa fa-university text-blue-600"></i>
      </div>
      <span class="text-sm font-semibold text-blue-600 uppercase tracking-wider">Banking & Reconciliation</span>
    </div>
    <h2 class="text-3xl font-bold text-gray-900 mb-4">Bank reconciliation without the headache</h2>
    <p class="text-lg text-gray-600 mb-12 max-w-2xl">Upload your bank statement, and Lekhya automatically matches transactions to your ledger entries. Unmatched items are flagged for quick review.</p>

    <div class="grid md:grid-cols-3 gap-6">
      @foreach([
        ['fa-upload','Statement Import','Upload bank statements in CSV or OFX format from any Indian bank. Passbook PDFs supported.'],
        ['fa-wand-magic-sparkles','Auto-Matching','Lekhya matches bank transactions to ledger entries by amount, date, and narration — no manual matching needed for most transactions.'],
        ['fa-list-check','Reconciliation Workflow','Review unmatched items, create missing ledger entries, and close the reconciliation period — all in one screen.'],
      ] as [$icon,$title,$desc])
      <div class="bg-white border border-gray-100 rounded-2xl p-6 hover:shadow-md transition">
        <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center mb-4">
          <i class="fa {{ $icon }} text-blue-600 text-sm"></i>
        </div>
        <h3 class="font-semibold text-gray-900 mb-2">{{ $title }}</h3>
        <p class="text-sm text-gray-600 leading-relaxed">{{ $desc }}</p>
      </div>
      @endforeach
    </div>
  </section>

  {{-- ── PRAMAAN ───────────────────────────────────────────── --}}
  <section id="pramaan">
    <div class="flex items-center space-x-3 mb-3">
      <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
        <i class="fa fa-briefcase text-amber-600"></i>
      </div>
      <span class="text-sm font-semibold text-amber-600 uppercase tracking-wider">Pramaan — CA Edition</span>
    </div>
    <div class="grid lg:grid-cols-2 gap-12 items-start">
      <div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Built for Chartered Accountants</h2>
        <p class="text-lg text-gray-600 mb-8">Pramaan is Lekhya's CA practice management layer. Manage all your clients from one dashboard, track UDIN, stay on top of filing deadlines, and run compliance audits — all within your Lekhya account.</p>
        <ul class="space-y-4">
          @foreach([
            ['fa-clipboard-list','UDIN Management','Generate, track, and search UDINs for all attestations and certifications across all your client accounts.'],
            ['fa-calendar-check','Compliance Calendar','Never miss a GST, Income Tax, or ROC deadline. Due-date alerts for every client in your portfolio.'],
            ['fa-people-group','Client Portfolio','See all your clients in one view — outstanding tasks, filing status, and last activity at a glance.'],
            ['fa-magnifying-glass-chart','Audit Reports','Generate practice-level reports — compliance score, pending attestations, and overdue tasks.'],
          ] as [$icon,$title,$desc])
          <li class="flex items-start space-x-4">
            <div class="w-9 h-9 bg-amber-50 border border-amber-200 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
              <i class="fa {{ $icon }} text-amber-600 text-sm"></i>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 text-sm">{{ $title }}</h3>
              <p class="text-sm text-gray-500">{{ $desc }}</p>
            </div>
          </li>
          @endforeach
        </ul>
      </div>
      <div class="bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200 rounded-2xl p-8">
        <div class="flex items-center space-x-2 mb-6">
          <i class="fa fa-star text-amber-500"></i>
          <span class="font-semibold text-amber-800">Pramaan CA Plan</span>
        </div>
        <p class="text-gray-700 text-sm leading-relaxed mb-6">Pramaan is available as an add-on to any Lekhya subscription. CAs get a discounted bundle rate when combined with client seat packs.</p>
        <a href="{{ route('marketing.pricing') }}" class="inline-flex items-center text-sm font-medium text-amber-700 hover:text-amber-800">
          See Pramaan pricing <i class="fa fa-arrow-right ml-2"></i>
        </a>
      </div>
    </div>
  </section>

  {{-- ── SECURITY ──────────────────────────────────────────── --}}
  <section id="security">
    <div class="flex items-center space-x-3 mb-3">
      <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center">
        <i class="fa fa-shield-halved text-gray-600"></i>
      </div>
      <span class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Security & Multi-Tenancy</span>
    </div>
    <h2 class="text-3xl font-bold text-gray-900 mb-4">Your data belongs to you — always</h2>
    <p class="text-lg text-gray-600 mb-12 max-w-2xl">Lekhya is a true multi-tenant SaaS. Every query is scoped to your account at the database level — no configuration required, no risk of data leakage.</p>

    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
      @foreach([
        ['fa-database','Row-Level Security','Every table in the database has a tenant_id column. PostgreSQL Row-Level Security policies enforce access at the database level — not just in application code.'],
        ['fa-users-gear','Role-Based Access','Invite team members as Owner, Accountant, or Viewer. Granular permissions powered by Spatie Laravel Permission.'],
        ['fa-clock-rotate-left','Immutable Audit Trail','Every change is logged — who did what and when. Journals cannot be edited once posted; the only way to correct is a reversing entry.'],
        ['fa-key','Single Sign-On','One Prabhas account for Lekhya, Seedha Bill, and all future apps. Sign in once, switch apps without logging in again.'],
      ] as [$icon,$title,$desc])
      <div class="bg-white border border-gray-100 rounded-2xl p-6 hover:shadow-md transition">
        <div class="w-9 h-9 bg-gray-100 rounded-lg flex items-center justify-center mb-4">
          <i class="fa {{ $icon }} text-gray-600 text-sm"></i>
        </div>
        <h3 class="font-semibold text-gray-900 mb-2">{{ $title }}</h3>
        <p class="text-sm text-gray-600 leading-relaxed">{{ $desc }}</p>
      </div>
      @endforeach
    </div>
  </section>

</div>

{{-- CTA --}}
<section class="bg-navy-600 text-white py-20 mt-10">
  <div class="max-w-3xl mx-auto px-4 text-center">
    <h2 class="text-3xl font-bold mb-4">Ready to get started?</h2>
    <p class="text-gray-300 text-lg mb-8">14-day free trial. No credit card. GST-ready on day 1.</p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-3.5 bg-blue-500 hover:bg-blue-400 text-white font-semibold rounded-xl transition text-lg">
        Start Free Trial <i class="fa fa-arrow-right ml-2"></i>
      </a>
      <a href="{{ route('marketing.pricing') }}" class="inline-flex items-center justify-center px-8 py-3.5 border border-white border-opacity-30 text-white hover:bg-white hover:bg-opacity-10 font-medium rounded-xl transition text-lg">
        View Plans
      </a>
    </div>
  </div>
</section>

<style>.no-scrollbar::-webkit-scrollbar{display:none}.no-scrollbar{-ms-overflow-style:none;scrollbar-width:none}</style>
@endsection
