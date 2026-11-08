{{-- Scaled banner sample for /design-system. Reuses the real component
     classes so a token change takes this down too. --}}
<div class="tr-banner" style="border-radius: var(--radius-lg); overflow: hidden; padding: 32px 24px 40px; margin-bottom: 16px;">
    <h1 class="tr-h1">Public transparency</h1>
    <span class="tr-goldbar" aria-hidden="true"></span>
    <p class="tr-privacy">Only complaints residents chose to share are listed. Exact addresses and private reports are never shown.</p>
    <details class="tr-how">
        <summary>
            How this data is shown
            <svg class="tr-how__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="tr-how__card">
            <ul>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 10"/></svg><span>Locations are grouped by barangay, not shown pinpoint by pinpoint.</span></li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 10"/></svg><span>Areas with very few reports are not highlighted.</span></li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 10"/></svg><span>Private complaints and likely spam are hidden.</span></li>
            </ul>
        </div>
    </details>
    <p class="tr-updated">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Updated Nov 8, 2026
    </p>
</div>
