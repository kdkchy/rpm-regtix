<x-filament-panels::page>
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Monitor perubahan pada filter event_id
            function setupFilterReactivity() {
                // Cari select filter untuk event_id menggunakan berbagai selector
                const selectors = [
                    'select[name*="event_id"]',
                    'select[data-name*="event_id"]',
                    'select[id*="event_id"]',
                    'select.wire\\:model*="event_id"',
                ];
                
                let eventSelect = null;
                for (const selector of selectors) {
                    eventSelect = document.querySelector(selector);
                    if (eventSelect) break;
                }
                
                if (eventSelect) {
                    // Hapus listener lama jika ada
                    const newEventSelect = eventSelect.cloneNode(true);
                    eventSelect.parentNode.replaceChild(newEventSelect, eventSelect);
                    eventSelect = newEventSelect;
                    
                    eventSelect.addEventListener('change', function() {
                        // Reset category_ticket_type_id filter
                        const categorySelectors = [
                            'select[name*="category_ticket_type_id"]',
                            'select[data-name*="category_ticket_type_id"]',
                            'select[id*="category_ticket_type_id"]',
                        ];
                        
                        for (const selector of categorySelectors) {
                            const categorySelect = document.querySelector(selector);
                            if (categorySelect) {
                                categorySelect.value = '';
                                // Trigger input event untuk Livewire
                                categorySelect.dispatchEvent(new Event('input', { bubbles: true }));
                                categorySelect.dispatchEvent(new Event('change', { bubbles: true }));
                                break;
                            }
                        }
                    });
                }
            }
            
            // Setup initial
            setupFilterReactivity();
            
            // Setup lagi setelah Livewire update
            if (typeof Livewire !== 'undefined') {
                document.addEventListener('livewire:load', setupFilterReactivity);
                Livewire.hook('morph.updated', () => {
                    setTimeout(setupFilterReactivity, 200);
                });
            }
            
            // Fallback: setup setiap 1 detik untuk memastikan
            setTimeout(setupFilterReactivity, 1000);
        });
    </script>
    @endpush
</x-filament-panels::page>
