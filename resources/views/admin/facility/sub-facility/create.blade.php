<x-admin.layout title="Add Sub Facility">
    <x-admin.card>
        <x-admin.card.card-header title="Add Sub Facility"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.facilities.sub-facilities.store') }}" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="name"
                            id="name"
                            label="Name"
                            value="{{ old('name') }}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="select"
                            name="facility_id"
                            id="facility_id"
                            label="Facility"
                            :options="$facilities"
                        />
                    </div>

                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="file"
                            name="icon"
                            id="icon"
                            label="Icon"
                        />
                    </div>
                    <div class="col-md-12">
                        <img width="50" height="50" src="" id="preview_icon" alt=""/>
                    </div>
                </div>
                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.facilities.sub-facilities.index') }}" class="btn btn-danger btn-sm">Back To Sub Facilities</a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">Add Sub Facility</x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
    @push('scripts')
    <script>
        $(document).ready(function () {
            $('#icon').on('change', function () {
                let reader = new FileReader();
                reader.onload = (e) => {
                    $('#preview_icon').attr('src', e.target.result);
                }
                reader.readAsDataURL(this.files[0]);
            });
        });
    </script>
@endpush
</x-admin.layout>
