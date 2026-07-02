@extends('layouts.app')
@section('title', 'Team & Permissions')
@section('page-title', 'Team & Permissions')

@section('content')
@php
// Graceful fallbacks so the page still renders if served by the old TenantController
// before web.php is updated to use UserManagementController.
$roles     ??= \Spatie\Permission\Models\Role::orderBy('name')->get();
$seatLimit ??= 5;
$seatsUsed ??= $users->count();
$isPramaan ??= auth()->user()->tenant?->isPramaan() ?? false;

$allModules = [
    'Invoices'  => ['view-invoices', 'manage-invoices'],
    'Journals'  => ['view-journals', 'manage-journals'],
    'GST'       => ['view-gst', 'manage-gst'],
    'Connector' => ['view-connector', 'manage-connector'],
    'Reports'   => ['view-reports'],
    'Banking'   => ['view-banking', 'manage-banking'],
    'AI'        => ['view-ai'],
    'Pramaan'   => ['view-pramaan', 'manage-pramaan'],
];

$permRows = [
    ['label' => 'Invoices',     'view' => 'view-invoices',   'manage' => 'manage-invoices',  'gated' => false],
    ['label' => 'Journals',     'view' => 'view-journals',   'manage' => 'manage-journals',  'gated' => false],
    ['label' => 'GST Filing',   'view' => 'view-gst',        'manage' => 'manage-gst',       'gated' => false],
    ['label' => 'Connector',    'view' => 'view-connector',  'manage' => 'manage-connector', 'gated' => false],
    ['label' => 'Reports',      'view' => 'view-reports',    'manage' => null,               'gated' => false],
    ['label' => 'Banking',      'view' => 'view-banking',    'manage' => 'manage-banking',   'gated' => false],
    ['label' => 'AI Tools',     'view' => 'view-ai',         'manage' => null,               'gated' => false],
    ['label' => 'Pramaan (CA)', 'view' => 'view-pramaan',   'manage' => 'manage-pramaan',   'gated' => true],
];

$roleSelectColors = [
    'owner'      => 'bg-navy-600 text-white focus:ring-navy-400',
    'accountant' => 'bg-blue-100 text-blue-800 focus:ring-blue-300',
    'ca'         => 'bg-purple-100 text-purple-800 focus:ring-purple-300',
    'viewer'     => 'bg-gray-100 text-gray-700 focus:ring-gray-300',
];
@endphp

<div x-data="{ showInvite: false, expandedUser: null }">

    {{-- ─── Page header ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <p class="text-gray-500 text-sm">
            Manage who can access this workspace and what they can do.
        </p>
        <div class="flex items-center gap-4 flex-shrink-0">
            {{-- Seat usage bar --}}
            <div class="hidden sm:flex items-center gap-2">
                <span class="text-xs text-gray-500 whitespace-nowrap">
                    {{ $seatsUsed }} / {{ $seatLimit }} seats used
                </span>
                <div class="w-24 bg-gray-200 rounded-full h-1.5">
                    @php $pct = $seatLimit > 0 ? min(100, ($seatsUsed / $seatLimit) * 100) : 0; @endphp
                    <div class="h-1.5 rounded-full {{ $seatsUsed >= $seatLimit ? 'bg-red-500' : 'bg-navy-600' }}"
                         style="width: {{ $pct }}%"></div>
                </div>
            </div>
            <button @click="showInvite = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-navy-600 text-white text-sm font-medium rounded-lg hover:bg-navy-700 transition-colors shadow-sm">
                <i class="fa fa-user-plus"></i> Invite Member
            </button>
        </div>
    </div>

    {{-- ─── User table ────────────────────────────────────────────────────────── --}}
    @if($users->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[700px]">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="w-12 pl-4 pr-2 py-3"></th>
                    <th class="text-left px-3 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Member</th>
                    <th class="text-left px-3 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Role</th>
                    <th class="text-left px-3 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide hidden lg:table-cell">Modules</th>
                    <th class="text-left px-3 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">

            @foreach($users as $user)
            @php
                $parts    = explode(' ', trim($user->name));
                $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                $role     = $user->getRoleNames()->first() ?? 'viewer';
                $selColor = $roleSelectColors[$role] ?? 'bg-gray-100 text-gray-700 focus:ring-gray-300';
                $isSelf   = $user->id === auth()->id();
                // Permission summary for Modules column
                $userPermNames  = $user->getPermissionNames()->all();
                $activeModNames = [];
                foreach ($allModules as $mName => $mPerms) {
                    if (!empty(array_intersect($mPerms, $userPermNames))) {
                        $activeModNames[] = $mName;
                    }
                }
                // JSON-safe permissions for Alpine
                $userPermsJson = json_encode(array_values($userPermNames));
            @endphp

            {{-- ── User row ──────────────────────────────────────────────── --}}
            <tr class="hover:bg-gray-50/70 transition-colors {{ !$user->is_active ? 'opacity-60' : '' }}">

                {{-- Avatar --}}
                <td class="pl-4 pr-2 py-3">
                    <div class="w-9 h-9 rounded-full bg-navy-600 flex items-center justify-center
                                text-white text-xs font-bold flex-shrink-0 select-none">
                        {{ $initials }}
                    </div>
                </td>

                {{-- Name + Email --}}
                <td class="px-3 py-3">
                    <div class="font-medium text-gray-900 flex items-center gap-1.5 flex-wrap">
                        {{ $user->name }}
                        @if($isSelf)
                            <span class="text-xs font-normal text-gray-400">(you)</span>
                        @endif
                    </div>
                    <div class="text-gray-400 text-xs mt-0.5">{{ $user->email }}</div>
                </td>

                {{-- Role — select dropdown styled as a pill badge --}}
                <td class="px-3 py-3">
                    <form method="POST" action="/settings/users/{{ $user->id }}/role">
                        @csrf
                        <input type="hidden" name="_method" value="PATCH">
                        <select name="role"
                                onchange="this.form.submit()"
                                class="text-xs font-semibold rounded-full px-2.5 py-1 border-0 ring-1 ring-inset
                                       ring-transparent focus:outline-none focus:ring-2 cursor-pointer
                                       appearance-none pr-5 {{ $selColor }}">
                            @foreach($roles as $r)
                                <option value="{{ $r->name }}" @selected($role === $r->name)>
                                    {{ ucfirst($r->name) }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </td>

                {{-- Modules (permission summary) --}}
                <td class="px-3 py-3 hidden lg:table-cell">
                    @if(empty($activeModNames))
                        <span class="text-gray-400 text-xs italic">Role defaults</span>
                    @else
                        <div class="flex flex-wrap gap-1">
                            @foreach(array_slice($activeModNames, 0, 3) as $mod)
                                <span class="text-xs bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded font-medium">
                                    {{ $mod }}
                                </span>
                            @endforeach
                            @if(count($activeModNames) > 3)
                                <span class="text-xs text-gray-400 self-center">
                                    +{{ count($activeModNames) - 3 }} more
                                </span>
                            @endif
                        </div>
                    @endif
                </td>

                {{-- Status pill --}}
                <td class="px-3 py-3">
                    @if($user->is_active)
                        <span class="inline-flex items-center gap-1 text-xs font-medium
                                     text-green-700 bg-green-50 rounded-full px-2 py-0.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 flex-shrink-0"></span>
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-xs font-medium
                                     text-red-600 bg-red-50 rounded-full px-2 py-0.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span>
                            Inactive
                        </span>
                    @endif
                </td>

                {{-- Actions --}}
                <td class="px-4 py-3">
                    <div class="flex items-center justify-end gap-1">

                        {{-- Toggle permission panel --}}
                        <button type="button"
                                @click="expandedUser = (expandedUser === {{ $user->id }}) ? null : {{ $user->id }}"
                                title="Edit permissions"
                                class="w-8 h-8 flex items-center justify-center rounded-lg
                                       text-gray-400 hover:text-navy-600 hover:bg-navy-50 transition-colors">
                            <i class="fa fa-sliders text-sm"
                               :class="expandedUser === {{ $user->id }} ? 'text-navy-600' : ''"></i>
                        </button>

                        @if(!$isSelf)
                            {{-- Deactivate / Reactivate --}}
                            @if($user->is_active)
                                <form method="POST" action="/settings/users/{{ $user->id }}/deactivate" class="inline">
                                    @csrf <input type="hidden" name="_method" value="PATCH">
                                    <button type="submit"
                                            onclick="return confirm('Deactivate {{ addslashes($user->name) }}?')"
                                            title="Deactivate"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg
                                                   text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-colors">
                                        <i class="fa fa-ban text-sm"></i>
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="/settings/users/{{ $user->id }}/reactivate" class="inline">
                                    @csrf <input type="hidden" name="_method" value="PATCH">
                                    <button type="submit"
                                            title="Reactivate"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg
                                                   text-gray-400 hover:text-green-600 hover:bg-green-50 transition-colors">
                                        <i class="fa fa-circle-check text-sm"></i>
                                    </button>
                                </form>
                            @endif

                            {{-- Remove --}}
                            <form method="POST" action="/settings/users/{{ $user->id }}" class="inline">
                                @csrf <input type="hidden" name="_method" value="DELETE">
                                <button type="submit"
                                        onclick="return confirm('Remove {{ addslashes($user->name) }} from this workspace? This cannot be undone.')"
                                        title="Remove member"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg
                                               text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                                    <i class="fa fa-trash text-sm"></i>
                                </button>
                            </form>
                        @else
                            {{-- Placeholder so the row height stays consistent --}}
                            <div class="w-16"></div>
                        @endif

                    </div>
                </td>
            </tr>

            {{-- ── Permission panel row (expands inline) ──────────────────── --}}
            <tr x-show="expandedUser === {{ $user->id }}" x-cloak>
                <td colspan="6" class="p-0 border-b border-navy-100">
                    <div class="px-6 py-5 bg-slate-50 border-t border-gray-100"
                         x-data="permPanel({{ $user->id }})"
                         data-perms="{{ json_encode(array_values($userPermNames)) }}">

                        {{-- Panel heading --}}
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-semibold text-gray-800">
                                <i class="fa fa-sliders text-navy-500 mr-1.5"></i>
                                Direct permissions &mdash; {{ $user->name }}
                            </h4>
                            <div class="flex items-center gap-3 text-xs">
                                <span x-show="saving" class="text-gray-500 flex items-center gap-1">
                                    <i class="fa fa-spinner fa-spin"></i> Saving…
                                </span>
                                <span x-show="saved" x-cloak class="text-green-600 font-medium flex items-center gap-1">
                                    <i class="fa fa-check"></i> Saved
                                </span>
                                <span class="text-gray-400 hidden sm:inline">
                                    Direct permissions override role defaults.
                                </span>
                            </div>
                        </div>

                        {{-- Permission matrix --}}
                        <div class="overflow-x-auto">
                            <table class="text-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left text-xs font-semibold text-gray-500 uppercase
                                                   tracking-wide pb-2 pr-10 w-44">Module</th>
                                        <th class="text-center text-xs font-semibold text-gray-500 uppercase
                                                   tracking-wide pb-2 w-16">View</th>
                                        <th class="text-center text-xs font-semibold text-gray-500 uppercase
                                                   tracking-wide pb-2 w-16">Manage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($permRows as $row)
                                @php $isGated = $row['gated'] && !$isPramaan; @endphp
                                <tr class="border-t border-gray-100/80 {{ $isGated ? 'opacity-40' : '' }}">
                                    <td class="py-2 pr-10 font-medium text-gray-700">
                                        {{ $row['label'] }}
                                        @if($isGated)
                                            <span class="ml-1.5 text-xs text-amber-600 font-normal">(Pramaan plan)</span>
                                        @endif
                                    </td>

                                    {{-- View checkbox --}}
                                    <td class="text-center py-2">
                                        @if($isGated)
                                            <input type="checkbox" disabled
                                                   class="rounded border-gray-300 cursor-not-allowed">
                                        @else
                                            <input type="checkbox"
                                                   :checked="hasPerm('{{ $row['view'] }}')"
                                                   @change="toggle('{{ $row['view'] }}', $event.target.checked)"
                                                   :disabled="saving"
                                                   class="rounded border-gray-300 text-navy-600
                                                          focus:ring-navy-500 cursor-pointer
                                                          disabled:cursor-not-allowed disabled:opacity-50">
                                        @endif
                                    </td>

                                    {{-- Manage checkbox (or dash if no manage perm for this module) --}}
                                    <td class="text-center py-2">
                                        @if(isset($row['manage']) && $row['manage'])
                                            @if($isGated)
                                                <input type="checkbox" disabled
                                                       class="rounded border-gray-300 cursor-not-allowed">
                                            @else
                                                <input type="checkbox"
                                                       :checked="hasPerm('{{ $row['manage'] }}')"
                                                       @change="toggle('{{ $row['manage'] }}', $event.target.checked)"
                                                       :disabled="saving"
                                                       class="rounded border-gray-300 text-navy-600
                                                              focus:ring-navy-500 cursor-pointer
                                                              disabled:cursor-not-allowed disabled:opacity-50">
                                            @endif
                                        @else
                                            <span class="text-gray-300 select-none">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <p class="mt-3 text-xs text-gray-400">
                            <i class="fa fa-circle-info mr-1"></i>
                            Changes take effect immediately. Uncheck all to fall back to role defaults.
                        </p>

                    </div>{{-- /x-data=permPanel --}}
                </td>
            </tr>

            @endforeach
            </tbody>
        </table>
        </div>{{-- /overflow-x-auto --}}
    </div>{{-- /card --}}

    @else
    {{-- ─── Empty state ──────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm py-20 text-center">
        <div class="w-16 h-16 bg-navy-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fa fa-users text-navy-400 text-2xl"></i>
        </div>
        <h3 class="text-gray-900 font-semibold text-base mb-1">No team members yet</h3>
        <p class="text-gray-500 text-sm mb-6 max-w-sm mx-auto">
            Invite your accountant or colleague to collaborate on this workspace.
        </p>
        <button @click="showInvite = true"
                class="inline-flex items-center gap-2 px-4 py-2 bg-navy-600 text-white
                       text-sm font-medium rounded-lg hover:bg-navy-700 transition-colors">
            <i class="fa fa-user-plus"></i> Invite Member
        </button>
    </div>
    @endif

    {{-- ─── Invite Member Modal ──────────────────────────────────────────────── --}}
    <div x-show="showInvite"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="showInvite = false">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
             @click="showInvite = false"></div>

        {{-- Modal panel --}}
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            {{-- Modal header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 bg-navy-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fa fa-user-plus text-navy-600 text-sm"></i>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900">Invite Team Member</h3>
                </div>
                <button type="button" @click="showInvite = false"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fa fa-xmark text-lg"></i>
                </button>
            </div>

            {{-- Modal body --}}
            <form method="POST" action="/settings/users/invite">
                @csrf
                <div class="px-6 py-5 space-y-4">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" required autocomplete="off"
                               placeholder="e.g. Priya Sharma"
                               value="{{ old('name') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900
                                      placeholder-gray-400 focus:outline-none focus:ring-2
                                      focus:ring-navy-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" required autocomplete="off"
                               placeholder="priya@example.com"
                               value="{{ old('email') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900
                                      placeholder-gray-400 focus:outline-none focus:ring-2
                                      focus:ring-navy-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select name="role" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900
                                       focus:outline-none focus:ring-2 focus:ring-navy-500
                                       focus:border-transparent bg-white">
                            <option value="">Select a role…</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->name }}" @selected(old('role') === $r->name)>
                                    {{ ucfirst($r->name) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            Owner: full access &middot; Accountant: finance ops &middot; CA: + Pramaan &middot; Viewer: read-only
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Personal Message
                            <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <textarea name="message" rows="2"
                                  placeholder="Welcome to the team! Let me know if you need any help."
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900
                                         placeholder-gray-400 focus:outline-none focus:ring-2
                                         focus:ring-navy-500 focus:border-transparent resize-none">{{ old('message') }}</textarea>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2.5 text-xs text-amber-700">
                        <i class="fa fa-triangle-exclamation mr-1"></i>
                        A temporary password will be generated and logged. Share it with the invitee
                        securely until email delivery is configured.
                    </div>

                </div>

                {{-- Modal footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                    <button type="button" @click="showInvite = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300
                                   rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium
                                   text-white bg-navy-600 rounded-lg hover:bg-navy-700 transition-colors shadow-sm">
                        <i class="fa fa-paper-plane"></i> Send Invite
                    </button>
                </div>
            </form>

        </div>{{-- /modal panel --}}
    </div>{{-- /invite modal --}}

</div>{{-- /x-data outer --}}
@endsection

@push('scripts')
<script>
/**
 * Per-user permission panel Alpine component.
 *
 * Reads initial permissions from the element's data-perms attribute (JSON array of
 * permission name strings). On any checkbox toggle, syncs the full permission set
 * back to the server via PATCH /settings/users/{id}/permissions.
 */
function permPanel(userId) {
    return {
        userId,
        perms: [],
        saving: false,
        saved: false,

        init() {
            const raw = this.$el.getAttribute('data-perms');
            try {
                this.perms = raw ? JSON.parse(raw) : [];
            } catch (e) {
                this.perms = [];
            }
        },

        hasPerm(name) {
            return this.perms.includes(name);
        },

        async toggle(name, checked) {
            if (checked) {
                if (!this.perms.includes(name)) {
                    this.perms.push(name);
                }
            } else {
                this.perms = this.perms.filter(p => p !== name);
            }
            await this.save();
        },

        async save() {
            this.saving = true;
            this.saved  = false;
            try {
                const token = document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content');

                const res = await fetch(`/settings/users/${this.userId}/permissions`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type':  'application/json',
                        'Accept':        'application/json',
                        'X-CSRF-TOKEN':  token,
                    },
                    body: JSON.stringify({ permissions: this.perms }),
                });

                if (res.ok) {
                    this.saved = true;
                    setTimeout(() => { this.saved = false; }, 2500);
                }
            } catch (e) {
                console.error('Permission update failed:', e);
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endpush
