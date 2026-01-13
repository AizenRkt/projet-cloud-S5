<div class="d-flex flex-column flex-shrink-0 p-3 bg-light h-100 border-end" style="width: 250px;">

    <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-dark text-decoration-none">
        <span class="fs-4">Menu</span>
    </a>
    <hr>

    <ul class="nav nav-pills flex-column mb-auto">

        <li class="nav-item">
            <a href="{{ route('categories.index') }}"
                class="nav-link {{ request()->is('categories*') ? 'active' : 'text-dark' }}">
                Categories
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('products.index') }}"
                class="nav-link {{ request()->is('products*') ? 'active' : 'text-dark' }}">
                Products
            </a>
        </li>

        <li>
            <a href="{{ url('/hello') }}" class="nav-link text-dark">Hello</a>
        </li>

        <li>
            <a href="{{ url('/about') }}" class="nav-link text-dark">About</a>
        </li>

    </ul>
</div>
