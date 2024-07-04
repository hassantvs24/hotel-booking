<x-admin.layout title="Update Facility">
    <x-admin.card>
        <x-admin.card.card-header title="Update Facility"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.facilities.update',$facility->id) }}" method="PUT" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-12">
                        <x-admin.input
                            type="text"
                            name="name"
                            id="name"
                            label="Name"
                            value="{{$facility->name}}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <x-admin.input
                            type="textarea"
                            name="notes"
                            id="notes"
                            label="Note"
                            value="{{$facility->notes}}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="facility_type"
                            id="facility_type"
                            label="facility_type"
                            value="{{$facility->facility_type}}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="select"
                            name="facility_for"
                            id="facility_for"
                            label="Facility For"
                            :options="$facilityForItems"
                            :value="$facility->facility_for"
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
                        <img width="50" height="50" src="{{$facility->icon_url}}" id="preview_icon" alt=""/>
                    </div>
                </div>
                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.facilities.index') }}" class="btn btn-danger btn-sm">Back To Facilities</a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">Update Facility</x-admin.button>
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
