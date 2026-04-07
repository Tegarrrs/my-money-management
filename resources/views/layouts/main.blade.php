<!DOCTYPE html>
<html lang="id">

@include('layouts.partial.head')

<body>
    <div class="app-shell">

        <!-- SIDEBAR -->
        @include('layouts.partial.sidebar')

        <!-- MAIN -->
        <div class="main-area">
            @include('layouts.partial.navbar')

            <main class="page-content">
                @yield('content')
            </main>
        </div>
    </div>


    <div class="toast-stack" id="toastStack"></div>
    @include('layouts.partial.js')

</body>

</html>