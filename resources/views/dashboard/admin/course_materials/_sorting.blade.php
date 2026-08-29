@if(filled($sectionId))
    @php
        $reorderUrl = route($reorderRouteName, [
            'type' => $type,
            'section' => $sectionId,
        ]);
    @endphp
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        function initSortable() {
            const tableBody = document.querySelector('#datatable tbody');
            if (!tableBody || tableBody.dataset.sortableApplied) return;

            tableBody.dataset.sortableApplied = true;

            Sortable.create(tableBody, {
                animation: 150,
                handle: null,
                onEnd: function () {
                    const order = [];
                    document.querySelectorAll('#datatable tbody tr').forEach((row, index) => {
                        order.push({
                            id: row.id,
                            order_by: index + 1
                        });
                    });

                    fetch(@json($reorderUrl), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token())
                        },
                        body: JSON.stringify({ order })
                    }).then(response => {
                        if (!response.ok) throw new Error('Unable to save material order');
                        return response.json();
                    }).then(response => {
                        if (response.status !== 'success') {
                            throw new Error('Unable to save material order');
                        }
                    }).catch(error => {
                        window.LaravelDataTables['datatable'].ajax.reload(null, false);
                        console.error(error);
                    });
                }
            });
        }

        $(document).ready(initSortable);
        $(document).on('draw.dt', initSortable);
    </script>
@endif
