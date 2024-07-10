<x-admin.layout title="Update Room">
    <x-admin.card>
        <x-admin.card.card-header title="Update Room"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.rooms.update',$room->id) }}" method="PUT" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-12">
                        <x-admin.input
                        type="text"
                        name="name"
                        id="name"
                        label="Name"
                        value="{{$room->name}}"
                    />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="room_number"
                        id="room_number"
                        label="Room Number"
                        value="{{$room->room_number}}"
                    />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="room_size"
                        id="room_size"
                        label="Room Size"
                        value="{{$room->room_size}}"
                    />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="guest_capacity"
                        id="guest_capacity"
                        label="Guest Capacity"
                        value="{{$room->guest_capacity}}"
                    />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="total_balcony"
                        id="total_balcony"
                        label="Total Balcony"
                        value="{{$room->total_balcony}}"
                    />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="total_window"
                        id="total_window"
                        label="Total Window"
                        value="{{$room->total_window}}"
                    />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="base_price"
                        id="base_price"
                        label="Price"
                        value="{{$room->base_price}}"
                    />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                        type="text"
                        name="notes"
                        id="notes"
                        label="Note"
                        value="{{$room->notes}}"
                    />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="select"
                        name="bed_type_id"
                        id="bed_type_id"
                        label="Bed Type"
                        :options="$bedTypes"
                        :value="$room->bed_type_id"
                    />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                        type="select"
                        name="room_type_id"
                        id="room_type_id"
                        label="Room Type"
                        :options="$roomTypes"
                        :value="$room->room_type_id"
                    />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="select"
                        name="property_id"
                        id="property_id"
                        label="Property"
                        :options="$properties"
                        :value="$room->property_id"
                    />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                        type="file"
                        name="photo"
                        id="photo"
                        label="Primary Image"
                    />
                    </div>
                    <div class="col-md-12">
                        <img
                            width="200"
                            height="200"
                            src="{{ $room->primary_image_url }}"
                            id="preview_icon"
                            alt=""
                        />
                    </div>

                    <div class="col-md-6 mt-4">
                        <x-admin.input
                            type="toggle"
                            name="extra_bed"
                            id="extra_bed"
                            label="Has Extra Bed ?"
                            value="{{ $room->extra_bed }}"
                        />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <x-admin.card>
                            <x-admin.card.card-header title="Room Images" />
                            <x-admin.card.card-body>
                                <div class="image-container mt-2">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="upload__box">
                                                <div class="upload__btn-box">
                                                    <label class="upload__btn">
                                                        <p>+</p>
                                                        <input type="file" multiple="" data-max_length="20" class="upload__inputfile">
                                                    </label>
                                                </div>
                                                <div class="upload__img-wrap"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </x-admin.card.card-body>
                        </x-admin.card>
                    </div>
                </div>

                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.rooms.index') }}" class="btn btn-danger btn-sm">
                            Back To List
                        </a>
                    </div>
                    <div class="col-md-6 text-right">
                        <x-admin.button variant="primary" type="submit" size="sm">
                           Update Room
                        </x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>

    @push('scripts')
    <script>
        $(document).ready(function () {
            $('#photo').on('change', function () {
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
            ImgUpload();
        });

        function ImgUpload() {
            var imgWrap = "";
            var imgArray = [];

            $('.upload__inputfile').each(function () {
                $(this).on('change', function (e) {
                    imgWrap = $(this).closest('.upload__box').find('.upload__img-wrap');
                    var maxLength = $(this).attr('data-max_length');

                    var files = e.target.files;
                    var filesArr = Array.prototype.slice.call(files);
                    var iterator = 0;
                    filesArr.forEach(function (f, index) {

                        if (!f.type.match('image.*')) {
                            return;
                        }

                        if (imgArray.length > maxLength) {
                            return false
                        } else {
                            var len = 0;
                            for (var i = 0; i < imgArray.length; i++) {
                                if (imgArray[i] !== undefined) {
                                    len++;
                                }
                            }
                            if (len > maxLength) {
                                return false;
                            } else {
                                imgArray.push(f);

                                var reader = new FileReader();
                                reader.onload = function (e) {
                                    var html = "<div class='upload__img-box'><div style='background-image: url(" + e.target.result + ")' data-number='" + $(".upload__img-close").length + "' data-file='" + f.name + "' class='img-bg'><div class='upload__img-close'></div></div></div>";
                                    imgWrap.append(html);
                                    iterator++;
                                }
                                reader.readAsDataURL(f);
                            }
                        }
                    });
                });
            });

            $('body').on('click', ".upload__img-close", function (e) {
                var file = $(this).parent().data("file");
                for (var i = 0; i < imgArray.length; i++) {
                    if (imgArray[i].name === file) {
                        imgArray.splice(i, 1);
                        break;
                    }
                }
                $(this).parent().parent().remove();
            });
        }
    </script>
@endpush
</x-admin.layout>
