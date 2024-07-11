<x-admin.layout title="Update Place">
    <x-admin.card>
        <x-admin.card.card-header title="Update Place"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.places.update', $place->id) }}" method="PUT" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="text"
                            name="name"
                            id="name"
                            placeholder="Place Name"
                            label="Place Name"
                            value="{{ $place->name }}"
                        />
                    </div>
                    <div class="col-md-6">
                    <x-admin.input
                            type="select"
                            name="city_id"
                            id="city_id"
                            label="City"
                            :options="$cities"
                            :value="$place->city_id"
                            additional-classes="searchable"
                        />
                    </div>
                </div>
            <div class="row">
                <div class="col-md-4">
                    <x-admin.input
                            type="number"
                            name="lat"
                            id="lat"
                            placeholder="Latitude"
                            label="Latitude"
                            value="{{ $place->lat }}"
                        />
                    </div>
                    <div class="col-md-4">
                        <x-admin.input
                            type="number"
                            name="long"
                            id="long"
                            placeholder="Longitude"
                            label="Longitude"
                            value="{{ $place->long }}"
                        />
                    </div>
                    <div class="col-md-4">
                        <x-admin.input
                        type="text"
                        name="zip_code"
                        id="zip_code"
                        placeholder="Zip Code"
                        label="Zip Code"
                        value="{{ $place->zip_code }}"
                    />
                    </div>
            </div>
                <div class="row">             
                    <div class="col-md-4">
                        <x-admin.input
                        type="text"
                        name="nearest_police"
                        id="nearest_police"
                        placeholder="Nearest Police "
                        label="Nearest Police "
                        value="{{ $place->nearest_police }}"
                    />
                    </div>
                    <div class="col-md-4">
                        <x-admin.input
                        type="text"
                        name="nearest_hospital"
                        id="nearest_hospital"
                        placeholder="Nearest Hospital"
                        label="Nearest Hospital"
                        value="{{ $place->nearest_hospital }}"
                    />
                    </div>
                    <div class="col-md-4">
                        <x-admin.input
                        type="text"
                        name="nearest_fire"
                        id="nearest_fire"
                        placeholder="Nearest Fire Station"
                        label="Nearest Fire Station"
                        value="{{ $place->nearest_fire }}"
                     />
                    </div>
                </div>
                <div class="row">
                <div class="col-12">
                 <x-admin.input
                    type="textarea"
                    name="description"
                    id="description"
                    placeholder="Description"
                    label="Description"
                    value="{{ $place->description }}"
                    />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                            type="file"
                            name="photo"
                            id="photo"
                            label="Photo"
                        />
                        <div class="col-md-12">
                            <img width="200" height="200" src="{{$place->primary_image_url}}" id="preview_photo" alt=""/>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="file"
                        name="icon"
                        id="icon"
                        label="Icon"
                        />
                        <div class="col-md-12">
                            <img width="200" height="200" src="{{ $place->icon_url }}" id="preview_icon" alt=""/>
                        </div>
                    </div>
                </div>
                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.places.index') }}" class="btn btn-danger btn-sm">Back To Places</a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">
                            Update Place
                        </x-admin.button>
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
    <script>
        $(document).ready(function () {
            $('#photo').on('change', function () {
                let reader = new FileReader();
                reader.onload = (e) => {
                    $('#preview_photo').attr('src', e.target.result);
                }
                reader.readAsDataURL(this.files[0]);
            });
        });
    </script>
@endpush
</x-admin.layout>
