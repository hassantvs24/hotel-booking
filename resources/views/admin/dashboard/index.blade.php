<x-admin.layout title="Dashboard">
    <div class="row">
        @foreach([1, 2, 3] as $widget)
            <div class="col-md-4">
                <x-admin.dashboard.widget
                    title="Total Users"
                    value="2,356"
                    description="New users this month"
                    iconClass="fa fa-users"
                    iconBgClass="bg-primary"
                />
            </div>
        @endforeach
    </div>
</x-admin.layout>
