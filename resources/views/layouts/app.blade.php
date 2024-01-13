<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- CSS yield -->
    @yield('css')

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>

<body>
    <div id="navbar">
        <div>
            <Sidebar v-model:visible="sidebarVisibility">
                <template #container="{ closeCallback }">
                    <div class="flex flex-column h-full">
                        <div class="flex align-items-center justify-content-between px-4 pt-3 flex-shrink-0">
                            <span class="inline-flex align-items-center gap-2">
                                <span
                                    class="font-semibold text-2xl text-primary">{{ config('app.name', 'Laravel') }}</span>
                            </span>
                            <span>
                                <p-button type="button" @click="toggleSidebar" icon="fa fa-xmark" rounded></p-button>
                            </span>
                        </div>
                        <div class="overflow-y-auto">
                            <ul class="list-none p-3 m-0">
                                <li>
                                    <div v-ripple
                                        v-styleclass="{
                                        selector: '@next',
                                        enterClass: 'hidden',
                                        enterActiveClass: 'slidedown',
                                        leaveToClass: 'hidden',
                                        leaveActiveClass: 'slideup'
                                    }"
                                        class="p-3 flex align-items-center justify-content-between text-600 cursor-pointer p-ripple">
                                        <span class="font-medium"><i class="fa fa-cube pr-2"></i>PRODUCTS</span>
                                        <i class="fa fa-angle-down"></i>
                                    </div>
                                    <ul class="list-none py-0 pl-3 pr-0 m-0 overflow-hidden">
                                        <li>
                                            <a v-ripple
                                                class="flex align-items-center cursor-pointer p-3 border-round text-700 hover:surface-100 transition-duration-150 transition-colors p-ripple">
                                                <i class="fa fa-grip mr-2"></i>
                                                <span class="font-medium">Galery</span>
                                            </a>
                                        </li>
                                        <li @click=redirect("/products")>
                                            <a v-ripple
                                                class="flex align-items-center cursor-pointer p-3 border-round text-700 hover:surface-100 transition-duration-150 transition-colors p-ripple">
                                                <i class="fa fa-box-open mr-2"></i>
                                                <span class="font-medium">Inventory</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li>
                                    <a v-ripple class="p-3 flex align-items-center text-600 cursor-pointer p-ripple">
                                        <i class="fa fa-table-list mr-2"></i>
                                        <span class="font-medium">REPORTS</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </template>
            </Sidebar>
            <Menubar :model="[]">
                <template #start>
                    <div class="flex flex-row flex-wrap">
                        <div class="flex">
                            <p-button icon="fa fa-bars" @click="toggleSidebar" />
                        </div>
                    </div>
                </template>
                <template #end>
                    <div class="card flex justify-content-center">
                        <p-button type="button" icon="fa fa-user" @click="toggleUserActions" aria-haspopup="true"
                            aria-controls="overlay_menu" />
                    </div>
                    <p-menu ref="userActions" id="overlay_menu" :model="userActions" :popup="true" />
                </template>
            </Menubar>
        </div>
        <main class="md:m-4 sm:m-0">
            @yield('content')
        </main>
    </div>
    @yield('js')
</body>

</html>
