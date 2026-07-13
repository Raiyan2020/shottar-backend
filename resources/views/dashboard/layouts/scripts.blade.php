    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->

    <script src="{{ asset('dashboard/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/libs/node-waves/node-waves.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/libs/hammer/hammer.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/libs/i18n/i18n.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/libs/typeahead-js/typeahead.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/js/menu.js') }}"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{ asset('dashboard/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    @yield('vendor-js')

        <!-- Flat Picker -->
    <script src="{{ asset('dashboard/assets/vendor/libs/moment/moment.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/libs/chartjs/chartjs.js') }}"></script>
    <!-- Main JS -->
    <script src="{{ asset('dashboard/assets/js/main.js') }}"></script>

    <script src="{{ asset('dashboard/assets/js/extended-ui-drag-and-drop.js') }}"></script>


    <!-- Page JS -->
    <script src="{{ asset('dashboard/assets/js/ui-popover.js') }}"></script>
{{--    <script src="{{ asset('dashboard/assets/js/charts-chartjs.js') }}"></script>--}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    @yield('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.dropdown-user .nav-link').addEventListener('click', function(event) {
                event.preventDefault();
                const dropdownMenu = this.nextElementSibling;
                dropdownMenu.classList.toggle('show');
            });
        });
        $(document).on('click', '.toggle-status', function () {
            const $badge = $(this);
            const url = $badge.data('url');

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.status) {
                        $('#datatable').DataTable().ajax.reload(null, false);
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'updated successfully',
                            showConfirmButton: false,
                            timer: 2500,
                            timerProgressBar: true,
                            background: '#ffffff',
                            color: '#3e2f1c',
                            customClass: {
                                popup: 'swal2-gold-shadow',
                                icon: 'swal2-gold-icon',
                                timerProgressBar: 'swal2-gold-progress'
                            }
                        });
                    }
                }
            });
        });

    </script>
    @if(Session::has('success'))
        <script>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: "{{ Session::get('success') }}",
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                background: '#ffffff',
                color: '#3e2f1c',
                customClass: {
                    popup: 'swal2-gold-shadow',
                    icon: 'swal2-gold-icon',
                    timerProgressBar: 'swal2-gold-progress'
                }
            });

        </script>
    @elseif(Session::has('error'))
        <script>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: "{{ Session::get('error') }}",
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                background: '#ffffff',
                color: '#3e2f1c',
                customClass: {
                    popup: 'swal2-gold-shadow',
                    icon: 'swal2-gold-icon',
                    timerProgressBar: 'swal2-gold-progress'
                }
            });
        </script>
    @endif



