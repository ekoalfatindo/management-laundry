<script module lang="ts">
    export type IconName =
        | 'dashboard'
        | 'receipt'
        | 'box'
        | 'scale'
        | 'washer'
        | 'layers'
        | 'staff'
        | 'customer'
        | 'users'
        | 'settings'
        | 'logout'
        | 'menu'
        | 'close'
        | 'plus'
        | 'edit'
        | 'trash'
        | 'search'
        | 'chevron-left'
        | 'chevron-right'
        | 'sun'
        | 'moon'
        | 'download'
        | 'print'
        | 'eye'
        | 'eye-off'
        | 'arrow-left'
        | 'check'
        | 'alert'
        | 'calendar'
        | 'wallet'
        | 'trend'
        | 'clock'
        | 'shirt';
</script>

<script lang="ts">
    let { name, class: className = 'size-5' }: { name: IconName; class?: string } = $props();
</script>

<svg
    class={className}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
>
    {#if name === 'dashboard'}
        <rect x="3" y="3" width="7" height="7" rx="1.5" />
        <rect x="14" y="3" width="7" height="7" rx="1.5" />
        <rect x="3" y="14" width="7" height="7" rx="1.5" />
        <rect x="14" y="14" width="7" height="7" rx="1.5" />
    {:else if name === 'receipt'}
        <path d="M6 3h12a1 1 0 0 1 1 1v17l-3-2-3 2-3-2-3 2V4a1 1 0 0 1 1-1Z" />
        <path d="M9 8h6M9 12h6M9 16h4" />
    {:else if name === 'box'}
        <path d="m21 8-9-5-9 5 9 5 9-5Z" />
        <path d="M3 8v8l9 5 9-5V8" />
        <path d="M12 13v8" />
    {:else if name === 'scale'}
        <path d="M12 3v18M7 21h10M5 7h14" />
        <path d="m5 7-3 6a3 3 0 0 0 6 0L5 7Z" />
        <path d="m19 7-3 6a3 3 0 0 0 6 0l-3-6Z" />
    {:else if name === 'washer'}
        <rect x="4" y="2.5" width="16" height="19" rx="2.5" />
        <circle cx="12" cy="13.5" r="5" />
        <circle cx="12" cy="13.5" r="2" />
        <path d="M8 6h2.5" />
        <circle cx="16.5" cy="6" r=".7" fill="currentColor" stroke="none" />
    {:else if name === 'settings'}
        <circle cx="12" cy="12" r="3" />
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" />
    {:else if name === 'layers'}
        <path d="m12 3 9 5-9 5-9-5 9-5Z" />
        <path d="m3 13 9 5 9-5" />
    {:else if name === 'staff'}
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
        <circle cx="9" cy="7" r="4" />
        <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
    {:else if name === 'customer'}
        <circle cx="12" cy="8" r="4" />
        <path d="M4 21a8 8 0 0 1 16 0" />
    {:else if name === 'users'}
        <circle cx="9" cy="8" r="3.5" />
        <path d="M2.5 20a6.5 6.5 0 0 1 13 0" />
        <path d="M16 5a3.5 3.5 0 0 1 0 7M18 14a5 5 0 0 1 3.5 6" />
    {:else if name === 'logout'}
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
        <path d="m16 17 5-5-5-5" />
        <path d="M21 12H9" />
    {:else if name === 'menu'}
        <path d="M4 6h16M4 12h16M4 18h16" />
    {:else if name === 'close'}
        <path d="m6 6 12 12M18 6 6 18" />
    {:else if name === 'plus'}
        <path d="M12 5v14M5 12h14" />
    {:else if name === 'edit'}
        <path d="M12 20h9" />
        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5Z" />
    {:else if name === 'trash'}
        <path d="M3 6h18" />
        <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" />
        <path d="m19 6-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
        <path d="M10 11v6M14 11v6" />
    {:else if name === 'search'}
        <circle cx="11" cy="11" r="7" />
        <path d="m20 20-3.5-3.5" />
    {:else if name === 'chevron-left'}
        <path d="m15 18-6-6 6-6" />
    {:else if name === 'chevron-right'}
        <path d="m9 18 6-6-6-6" />
    {:else if name === 'sun'}
        <circle cx="12" cy="12" r="4" />
        <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
    {:else if name === 'moon'}
        <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" />
    {:else if name === 'download'}
        <path d="M12 3v12" />
        <path d="m7 10 5 5 5-5" />
        <path d="M5 21h14" />
    {:else if name === 'print'}
        <path d="M6 9V3h12v6" />
        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
        <rect x="6" y="14" width="12" height="7" rx="1" />
    {:else if name === 'eye'}
        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
        <circle cx="12" cy="12" r="3" />
    {:else if name === 'eye-off'}
        <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
        <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
        <path d="M6.61 6.61A13.53 13.53 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
        <path d="m2 2 20 20" />
    {:else if name === 'arrow-left'}
        <path d="M19 12H5" />
        <path d="m12 19-7-7 7-7" />
    {:else if name === 'check'}
        <path d="m5 13 4 4L19 7" />
    {:else if name === 'alert'}
        <path d="M12 9v4M12 17h.01" />
        <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
    {:else if name === 'calendar'}
        <rect x="3" y="5" width="18" height="16" rx="2" />
        <path d="M16 3v4M8 3v4M3 10h18" />
    {:else if name === 'wallet'}
        <path d="M20 7H5a2 2 0 0 1 0-4h13v4" />
        <path d="M3 5v14a2 2 0 0 0 2 2h15V7H5" />
        <path d="M16 13h2" />
    {:else if name === 'trend'}
        <path d="m3 17 6-6 4 4 8-8" />
        <path d="M15 7h6v6" />
    {:else if name === 'clock'}
        <circle cx="12" cy="12" r="9" />
        <path d="M12 7v5l3 2" />
    {:else if name === 'shirt'}
        <path d="M9 3 4 5.5 2 10l3.5 1.5L6 21h12l.5-9.5L22 10l-2-4.5L15 3a3 3 0 0 1-6 0Z" />
    {/if}
</svg>
