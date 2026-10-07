@props(['name' => 'sparkles'])
<svg {{ $attributes->merge(['class'=>'size-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('settings') <path d="M4 6h16M4 12h16M4 18h16"/><circle cx="8" cy="6" r="2" fill="currentColor"/><circle cx="16" cy="12" r="2" fill="currentColor"/><circle cx="10" cy="18" r="2" fill="currentColor"/> @break
@case('calendar') <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18m-13 5h2m4 0h2"/> @break
@case('eye') <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/> @break
@case('edit') <path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4 10.5-10.5Z"/> @break
@case('archive') <rect x="3" y="3" width="18" height="4" rx="1"/><path d="M5 7v14h14V7M10 11h4"/> @break
@case('stock-in') <path d="M12 3v10m-4-4 4 4 4-4M5 12H3v9h18v-9h-2M3 17h5l2 2h4l2-2h5"/> @break
@case('stock-out') <path d="M12 13V3m-4 4 4-4 4 4M5 12H3v9h18v-9h-2M3 17h5l2 2h4l2-2h5"/> @break
@case('cart') <path d="M2 3h3l3 13h11l3-9H6"/><circle cx="9" cy="21" r="1"/><circle cx="19" cy="21" r="1"/> @break
@case('receipt') <path d="M5 3h14v19l-3-2-4 2-4-2-3 2V3ZM9 7h6M9 11h6M9 15h3"/> @break
@case('grid') <rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/> @break
@case('users') <circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 5"/> @break
@case('flower') <path d="M12 12C2 12 3 3 7 5c0-7 10-7 10 0 5-2 6 7-5 7Zm0 0c-10 0-9 9-5 7 0 7 10 7 10 0 5 2 6-7-5-7Z"/> @break
@case('scissors') <circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="m8 8 13 13M8 16 21 3"/> @break
@case('layers') <path d="m12 3 10 5-10 5L2 8l10-5Zm-10 9 10 5 10-5M2 16l10 5 10-5"/> @break
@case('shield') <path d="M12 3 3 7v6c0 5 9 9 9 9s9-4 9-9V7l-9-4Z"/><path d="m8 12 3 3 5-6"/> @break
@case('search') <circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/> @break
@case('plus') <path d="M12 5v14M5 12h14"/> @break
@case('arrow') <path d="M4 12h16m-6-6 6 6-6 6"/> @break
@case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
@case('logout') <path d="M9 3H4v18h5M9 12h12m-5-5 5 5-5 5"/> @break
@case('bell') <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/> @break
@case('close') <path d="m6 6 12 12M6 18 18 6"/> @break
@case('chevron-down') <path d="m6 9 6 6 6-6"/> @break
@case('user') <circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/> @break
@case('menu') <path d="M4 6h16M4 12h16M4 18h16"/> @break
@case('check') <path d="m5 12 4 4L19 6"/> @break
@case('mail') <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/> @break
@case('phone') <path d="M7 3H3c0 10 8 18 18 18v-4l-5-2-2 2a15 15 0 0 1-7-7l2-2-2-5Z"/> @break
@default <path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5L12 3Z"/>
@endswitch
</svg>
