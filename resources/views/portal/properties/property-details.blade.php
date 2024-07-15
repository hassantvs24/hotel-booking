<x-portal.layout title="Show Room">
    <section class="rooms-section-title">
        <div class="container">
           <div class="row align-items-end">
              <div class="col-xl-9 col-md-12">
                 <h4>{{ $property->name }}</h4>
                 <p>{{ $property->place->name }}</p>
              </div>
              <div class="col-xl-3 col-md-12 d-flex justify-content-end">
                 <a href="">
                    <svg width="14" height="17" viewBox="0 0 14 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                       <path d="M11.5 16.5C10.8053 16.5 10.215 16.257 9.729 15.771C9.243 15.285 9 14.6947 9 14C9 13.8887 9.007 13.788 9.021 13.698C9.035 13.6073 9.05567 13.5067 9.083 13.396L4.125 10.375C3.91633 10.597 3.66967 10.7567 3.385 10.854C3.10033 10.9513 2.80533 11 2.5 11C1.80533 11 1.215 10.757 0.729 10.271C0.243 9.785 0 9.19467 0 8.5C0 7.80533 0.243 7.215 0.729 6.729C1.215 6.243 1.80533 6 2.5 6C2.80533 6 3.10033 6.052 3.385 6.156C3.66967 6.26 3.91633 6.41633 4.125 6.625L9.083 3.604C9.05567 3.49333 9.035 3.39267 9.021 3.302C9.007 3.212 9 3.11133 9 3C9 2.30533 9.243 1.715 9.729 1.229C10.215 0.743 10.8053 0.5 11.5 0.5C12.1947 0.5 12.785 0.743 13.271 1.229C13.757 1.715 14 2.30533 14 3C14 3.69467 13.757 4.285 13.271 4.771C12.785 5.257 12.1947 5.5 11.5 5.5C11.1947 5.5 10.8997 5.45133 10.615 5.354C10.3303 5.25667 10.0837 5.097 9.875 4.875L4.917 7.896C4.94433 8.00667 4.965 8.10733 4.979 8.198C4.993 8.288 5 8.38867 5 8.5C5 8.61133 4.993 8.712 4.979 8.802C4.965 8.89267 4.94433 8.99333 4.917 9.104L9.875 12.125C10.0837 11.889 10.3303 11.7257 10.615 11.635C10.8997 11.545 11.1947 11.5 11.5 11.5C12.1947 11.5 12.785 11.743 13.271 12.229C13.757 12.715 14 13.3053 14 14C14 14.6947 13.757 15.285 13.271 15.771C12.785 16.257 12.1947 16.5 11.5 16.5ZM11.5 4C11.778 4 12.014 3.90267 12.208 3.708C12.4027 3.514 12.5 3.278 12.5 3C12.5 2.722 12.4027 2.486 12.208 2.292C12.014 2.09733 11.778 2 11.5 2C11.222 2 10.986 2.09733 10.792 2.292C10.5973 2.486 10.5 2.722 10.5 3C10.5 3.278 10.5973 3.514 10.792 3.708C10.986 3.90267 11.222 4 11.5 4ZM2.5 9.5C2.778 9.5 3.014 9.40267 3.208 9.208C3.40267 9.014 3.5 8.778 3.5 8.5C3.5 8.222 3.40267 7.986 3.208 7.792C3.014 7.59733 2.778 7.5 2.5 7.5C2.222 7.5 1.986 7.59733 1.792 7.792C1.59733 7.986 1.5 8.222 1.5 8.5C1.5 8.778 1.59733 9.014 1.792 9.208C1.986 9.40267 2.222 9.5 2.5 9.5ZM11.5 15C11.778 15 12.014 14.9027 12.208 14.708C12.4027 14.514 12.5 14.278 12.5 14C12.5 13.722 12.4027 13.486 12.208 13.292C12.014 13.0973 11.778 13 11.5 13C11.222 13 10.986 13.0973 10.792 13.292C10.5973 13.486 10.5 13.722 10.5 14C10.5 14.278 10.5973 14.514 10.792 14.708C10.986 14.9027 11.222 15 11.5 15Z" fill="#64748B"></path>
                    </svg>
                    Share
                 </a>
                 <a href="">
                    <svg width="20" height="21" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                       <mask id="mask0_277_1584" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="20" height="21">
                          <rect y="0.5" width="20" height="20" fill="#D9D9D9"></rect>
                       </mask>
                       <g mask="url(#mask0_277_1584)">
                          <path d="M8.83299 16.938L7.54199 15.771C5.98599 14.3683 4.61799 13.0073 3.43799 11.688C2.25733 10.368 1.66699 8.90932 1.66699 7.31199C1.66699 6.00665 2.10799 4.90965 2.98999 4.02099C3.87133 3.13232 4.97199 2.68799 6.29199 2.68799C7.04199 2.68799 7.73633 2.84399 8.37499 3.15599C9.01366 3.46865 9.55533 3.86799 9.99999 4.35399C10.4307 3.86799 10.9653 3.46865 11.604 3.15599C12.2427 2.84399 12.9373 2.68799 13.688 2.68799C15.0073 2.68799 16.1113 3.13232 17 4.02099C17.8887 4.90965 18.333 6.00665 18.333 7.31199C18.333 8.90932 17.7323 10.3747 16.531 11.708C15.3297 13.0413 13.9097 14.4513 12.271 15.938L11.167 16.938C10.8337 17.2433 10.4447 17.3923 9.99999 17.385C9.55533 17.3783 9.16633 17.2293 8.83299 16.938ZM9.16699 6.04199C8.93033 5.65265 8.55166 5.28799 8.03099 4.94799C7.51033 4.60799 6.93066 4.43799 6.29199 4.43799C5.45866 4.43799 4.77099 4.70865 4.22899 5.24999C3.68766 5.79132 3.41699 6.47865 3.41699 7.31199C3.41699 8.03465 3.67033 8.79166 4.17699 9.58299C4.68433 10.375 5.29199 11.146 5.99999 11.896C6.70799 12.646 7.43366 13.344 8.17699 13.99C8.92033 14.6353 9.52799 15.1803 9.99999 15.625C10.4587 15.2083 11.0593 14.67 11.802 14.01C12.5453 13.3507 13.271 12.6423 13.979 11.885C14.6877 11.1283 15.2987 10.354 15.812 9.56199C16.326 8.77066 16.583 8.02065 16.583 7.31199C16.583 6.47865 16.3087 5.79132 15.76 5.24999C15.212 4.70865 14.5213 4.43799 13.688 4.43799C13.0487 4.43799 12.479 4.60799 11.979 4.94799C11.479 5.28799 11.104 5.65265 10.854 6.04199C10.7567 6.19465 10.6317 6.31265 10.479 6.39599C10.3263 6.47932 10.1667 6.52099 9.99999 6.52099C9.84733 6.52099 9.69466 6.47932 9.54199 6.39599C9.38866 6.31265 9.26366 6.19465 9.16699 6.04199Z" fill="#64748B"></path>
                       </g>
                    </svg>
                    Save
                 </a>
              </div>
           </div>
        </div>
     </section>
     <section class="rooms-gallery">
        <div class="container">
           <div class="row rooms-gallery-border">
              <div class="col-md-6">
                 <div class="single-room-gallery position-relative">
                    <img src="{{ $property->primary_image_url }}" class="w-100" alt="">
                    <a href="" class="btn btn-border" data-bs-toggle="modal" data-bs-target="#galleryModal"><img src="assets/img/icons/icon-gallery.png" alt=""> Show All Photos</a>
                 </div>
              </div>
              <div class="col-md-6">
                 <div class="row align-content-center flex-wrap">
                    <div class="col-md-6">
                       <img src="{{ $property->primary_image_url }}" class="w-100 position-relative" alt="">
                    </div>
                    <div class="col-md-6">
                       <img src="{{ $property->primary_image_url }}" class="w-100" alt="">
                    </div>
                    <div class="col-md-6 mt-4">
                       <img src="{{ $property->primary_image_url }}" class="w-100" alt="">
                    </div>
                    <div class="col-md-6 mt-4">
                       <img src="{{ $property->primary_image_url }}" class="w-100" alt="">
                    </div>
                 </div>
              </div>
           </div>
        </div>
     </section>

     <!-- Gallery Modal -->
     <section class="room-amentites">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="review-summary d-flex justify-content-between align-items-end">
                        <div class="review-summary-content d-flex">
                            <h4>{{ $property->rating}}</h4>
                            <p>Very Good <span>1234 reviews</span></p>
                        </div>
                        <span><a href="">All Reviews <img src="assets/img/icons/icon-down-arrow.png" alt=""></a></span>
                    </div>
                    <h4 class="room-amentites-title">Popular Amenities</h4>
                    <div class="row">
                        @foreach ($property->facilities as $facility)
                        <div class="col-md-6">
                            <span><img src="{{$facility->icon_url}}" alt=""> {{$facility->name}}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-lg-6">
                        <div class="room-booking-form">
                            <p>Offered Price</p>
                            <h4>BDT 14,906 <span>/night</span></h4>
                            <form action="">
                                <div class="review-input">
                                    <div class="review-input-box">  
                                        <svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <mask id="mask0_281_1609" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="24" height="25">
                                        <rect y="0.5" width="24" height="24" fill="#D9D9D9"></rect>
                                        </mask>
                                        <g mask="url(#mask0_281_1609)">
                                        <path d="M17 22.5V19.5H14V17.5H17V14.5H19V17.5H22V19.5H19V22.5H17ZM5 20.5C4.45 20.5 3.979 20.3043 3.587 19.913C3.19567 19.521 3 19.05 3 18.5V6.5C3 5.95 3.19567 5.47933 3.587 5.088C3.979 4.696 4.45 4.5 5 4.5H6V2.5H8V4.5H14V2.5H16V4.5H17C17.55 4.5 18.021 4.696 18.413 5.088C18.8043 5.47933 19 5.95 19 6.5V12.6C18.6667 12.55 18.3333 12.525 18 12.525C17.6667 12.525 17.3333 12.55 17 12.6V10.5H5V18.5H12C12 18.8333 12.025 19.1667 12.075 19.5C12.125 19.8333 12.2167 20.1667 12.35 20.5H5ZM5 8.5H17V6.5H5V8.5Z" fill="#14B8A6"></path>
                                        </g>
                                        </svg>
                                        <input type="text" placeholder="Check In" class="date-picker hasDatepicker" id="dp1720541142932">
                                    </div>
                                    <div class="review-input-box">
                                        <svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <mask id="mask0_281_1609" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="24" height="25">
                                        <rect y="0.5" width="24" height="24" fill="#D9D9D9"></rect>
                                        </mask>
                                        <g mask="url(#mask0_281_1609)">
                                        <path d="M17 22.5V19.5H14V17.5H17V14.5H19V17.5H22V19.5H19V22.5H17ZM5 20.5C4.45 20.5 3.979 20.3043 3.587 19.913C3.19567 19.521 3 19.05 3 18.5V6.5C3 5.95 3.19567 5.47933 3.587 5.088C3.979 4.696 4.45 4.5 5 4.5H6V2.5H8V4.5H14V2.5H16V4.5H17C17.55 4.5 18.021 4.696 18.413 5.088C18.8043 5.47933 19 5.95 19 6.5V12.6C18.6667 12.55 18.3333 12.525 18 12.525C17.6667 12.525 17.3333 12.55 17 12.6V10.5H5V18.5H12C12 18.8333 12.025 19.1667 12.075 19.5C12.125 19.8333 12.2167 20.1667 12.35 20.5H5ZM5 8.5H17V6.5H5V8.5Z" fill="#14B8A6"></path>
                                        </g>
                                        </svg>
                                        <input type="text" placeholder="Check Out" class="date-picker hasDatepicker" id="dp1720541142933">
                                    </div>
                                </div>
                                <div class="review-input">
                            <div class="review-input-box">
                               <svg width="24" height="17" viewBox="0 0 24 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                  <path d="M12.5 8.45C12.9833 7.91667 13.3543 7.30833 13.613 6.625C13.871 5.94167 14 5.23333 14 4.5C14 3.76667 13.871 3.05833 13.613 2.375C13.3543 1.69167 12.9833 1.08333 12.5 0.55C13.5 0.683334 14.3333 1.125 15 1.875C15.6667 2.625 16 3.5 16 4.5C16 5.5 15.6667 6.375 15 7.125C14.3333 7.875 13.5 8.31667 12.5 8.45ZM17.525 16.5C17.675 16.3833 17.7917 16.2373 17.875 16.062C17.9583 15.8873 18 15.6917 18 15.475V13.5C18 12.9 17.8667 12.3293 17.6 11.788C17.3333 11.246 16.9833 10.7667 16.55 10.35C17.4 10.65 18.1877 11.0373 18.913 11.512C19.6377 11.9873 20 12.65 20 13.5V15.5C20 15.7833 19.904 16.0207 19.712 16.212C19.5207 16.404 19.2833 16.5 19 16.5H17.525ZM21 9.5C20.7167 9.5 20.4793 9.404 20.288 9.212C20.096 9.02067 20 8.78333 20 8.5V7.5H19C18.7167 7.5 18.4793 7.404 18.288 7.212C18.096 7.02067 18 6.78333 18 6.5C18 6.21667 18.096 5.979 18.288 5.787C18.4793 5.59567 18.7167 5.5 19 5.5H20V4.5C20 4.21667 20.096 3.979 20.288 3.787C20.4793 3.59567 20.7167 3.5 21 3.5C21.2833 3.5 21.5207 3.59567 21.712 3.787C21.904 3.979 22 4.21667 22 4.5V5.5H23C23.2833 5.5 23.5207 5.59567 23.712 5.787C23.904 5.979 24 6.21667 24 6.5C24 6.78333 23.904 7.02067 23.712 7.212C23.5207 7.404 23.2833 7.5 23 7.5H22V8.5C22 8.78333 21.904 9.02067 21.712 9.212C21.5207 9.404 21.2833 9.5 21 9.5ZM8 8.5C6.9 8.5 5.95833 8.10833 5.175 7.325C4.39167 6.54167 4 5.6 4 4.5C4 3.4 4.39167 2.45833 5.175 1.675C5.95833 0.891667 6.9 0.5 8 0.5C9.1 0.5 10.0417 0.891667 10.825 1.675C11.6083 2.45833 12 3.4 12 4.5C12 5.6 11.6083 6.54167 10.825 7.325C10.0417 8.10833 9.1 8.5 8 8.5ZM1 16.5C0.716667 16.5 0.479333 16.404 0.288 16.212C0.096 16.0207 0 15.7833 0 15.5V13.7C0 13.1333 0.146 12.6123 0.438 12.137C0.729333 11.6623 1.11667 11.3 1.6 11.05C2.63333 10.5333 3.68333 10.1457 4.75 9.887C5.81667 9.629 6.9 9.5 8 9.5C9.1 9.5 10.1833 9.629 11.25 9.887C12.3167 10.1457 13.3667 10.5333 14.4 11.05C14.8833 11.3 15.2707 11.6623 15.562 12.137C15.854 12.6123 16 13.1333 16 13.7V15.5C16 15.7833 15.9043 16.0207 15.713 16.212C15.521 16.404 15.2833 16.5 15 16.5H1ZM8 6.5C8.55 6.5 9.021 6.304 9.413 5.912C9.80433 5.52067 10 5.05 10 4.5C10 3.95 9.80433 3.47933 9.413 3.088C9.021 2.696 8.55 2.5 8 2.5C7.45 2.5 6.97933 2.696 6.588 3.088C6.196 3.47933 6 3.95 6 4.5C6 5.05 6.196 5.52067 6.588 5.912C6.97933 6.304 7.45 6.5 8 6.5ZM2 14.5H14V13.7C14 13.5167 13.9543 13.35 13.863 13.2C13.771 13.05 13.65 12.9333 13.5 12.85C12.6 12.4 11.6917 12.0623 10.775 11.837C9.85833 11.6123 8.93333 11.5 8 11.5C7.06667 11.5 6.14167 11.6123 5.225 11.837C4.30833 12.0623 3.4 12.4 2.5 12.85C2.35 12.9333 2.22933 13.05 2.138 13.2C2.046 13.35 2 13.5167 2 13.7V14.5Z" fill="#14B8A6"></path>
                               </svg>
                               <input type="number" name="number" min="0" max="100" placeholder="2 Adult">
                               <button type="button" onclick="this.parentNode.querySelector('[type=number]').stepDown();">
                               <img src="assets/img/home/arrow_drop_up.png" alt="">
                               </button>
                               <button type="button" onclick="this.parentNode.querySelector('[type=number]').stepUp();">
                               <img src="assets/img/home/arrow_drop_down.png" alt="">
                               </button>
                            </div>
                            <div class="review-input-box">
                               <input type="number" name="number" min="0" max="100" placeholder="0 Child">
                               <button type="button" onclick="this.parentNode.querySelector('[type=number]').stepDown();">
                               <img src="assets/img/home/arrow_drop_up.png" alt="">
                               </button>
                               <button type="button" onclick="this.parentNode.querySelector('[type=number]').stepUp();">
                               <img src="assets/img/home/arrow_drop_down.png" alt="">
                               </button>
                            </div>
                            <div class="review-input-box">
                               <input type="number" name="number" min="0" max="100" placeholder="1 Room">
                               <button type="button" onclick="this.parentNode.querySelector('[type=number]').stepDown();">
                               <img src="assets/img/home/arrow_drop_up.png" alt="">
                               </button>
                               <button type="button" onclick="this.parentNode.querySelector('[type=number]').stepUp();">
                               <img src="assets/img/home/arrow_drop_down.png" alt="">
                               </button>
                            </div>
                         </div>
                                <input type="submit" value="Check Available Rooms" class="btn-bg d-block w-100">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
    </section>
    <section class="single-room-column-area">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h4 class="section-title">Available Rooms You May Like</h4>
                </div>
            </div>
            <div class="row">
                @foreach ($property->rooms as $room)
                <div class="col-md-6 col-lg-4">
                    <div class="single-room-column">
                        <div class="room-column-slider">
                            <span class="room-column-icon">
                                <a href=""><img src="assets/img/icons/icon-heart.png" alt=""></a>
                            </span>
                            <div id="room-indicator-1" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-indicators">
                                    <button type="button" data-bs-target="#room-indicator-1" data-bs-slide-to="0" class=""></button>
                                    <button type="button" data-bs-target="#room-indicator-1" data-bs-slide-to="1" class=""></button>
                                    <button type="button" data-bs-target="#room-indicator-1" data-bs-slide-to="2" class="active" aria-current="true"></button>
                                </div>
                                <div class="carousel-inner">
                                    {{-- <div class="carousel-item">
                                    <img src="assets/img/rooms/room-1.png" class="d-block w-100" alt="...">
                                    </div>
                                    <div class="carousel-item">
                                    <img src="assets/img/rooms/room-2.png" class="d-block w-100" alt="...">
                                    </div> --}}
                                    <div class="carousel-item active">
                                    <img src="{{$room->primary_image_url}}" class="d-block w-100" alt="...">
                                    </div>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#room-indicator-1" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true">
                                        <img src="assets/img/icons/arrow_back.png" alt="">
                                    </span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#room-indicator-1" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true">
                                        <img src="assets/img/icons/arrow_forward.png" alt="">
                                    </span>
                                </button>
                            </div>
                        </div>
                        <div class="room-column-content">
                            <h5>{{$room->name}}</h5>
                            <span><img src="assets/img/icons/icon-sq-ft.png" alt="">1012 sq ft</span>
                            <span><img src="assets/img/icons/icon-beach-view.png" alt="">Beach view</span>
                            <span><img src="assets/img/icons/icon-beds.png" alt="">2 Twin Beds</span>
                            <span><img src="assets/img/icons/icon-guests.png" alt="">4 Guests</span>
                            <span><img src="assets/img/icons/icon-welcome-drink.png" alt="">Welcome Drink</span>
                            <span><img src="assets/img/icons/icon-breakfast.png" alt="">Breakfast for 4</span>
                            <span><img src="assets/img/icons/icon-tea.png" alt="">Room Tea/Coffee</span>
                            <span><img src="assets/img/icons/icon-room.png" alt="">24 h room service</span>
                            <span><img src="assets/img/icons/icon-car.png" alt="">Free parking</span>
                            <span><img src="assets/img/icons/icon-air-condiitoner.png" alt="">Air Conditioner</span>
                        </div>
                        <div class="room-column-bottom">
                            <p>BDT {{$room->base_price}} <span>/night</span></p>
                            <a href="" class="btn btn-bg">Reserve The Room</a>
                            <a href="" class="btn btn-border">Make An Offer</a>
                        </div>
                    </div>
                </div>            
                @endforeach
            </div>
        </div>
    </section>
    <section class="single-room-column-area">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h4 class="section-title">Other Rooms</h4>
                    <p class="section-title-desc">These rooms are already booked during your travel date. But <br> you can make an offer for the next booking. </p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-lg-4">
                    <div class="single-room-column">
                        <div class="room-column-slider">
                            <span class="room-column-icon">
                                <a href=""><img src="assets/img/icons/icon-heart.png" alt=""></a>
                            </span>
                            <div id="room-indicator-1" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-indicators">
                                    <button type="button" data-bs-target="#room-indicator-1" data-bs-slide-to="0" class="active" aria-current="true"></button>
                                    <button type="button" data-bs-target="#room-indicator-1" data-bs-slide-to="1" class=""></button>
                                    <button type="button" data-bs-target="#room-indicator-1" data-bs-slide-to="2" class=""></button>
                                </div>
                                <div class="carousel-inner">
                                    <div class="carousel-item active">
                                    <img src="assets/img/rooms/room-1.png" class="d-block w-100" alt="...">
                                    </div>
                                    <div class="carousel-item">
                                    <img src="assets/img/rooms/room-2.png" class="d-block w-100" alt="...">
                                    </div>
                                    <div class="carousel-item">
                                    <img src="assets/img/rooms/room-3.png" class="d-block w-100" alt="...">
                                    </div>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#room-indicator-1" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true">
                                        <img src="assets/img/icons/arrow_back.png" alt="">
                                    </span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#room-indicator-1" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true">
                                        <img src="assets/img/icons/arrow_forward.png" alt="">
                                    </span>
                                </button>
                            </div>
                        </div>
                        <div class="room-column-content">
                            <h5>Sea View Studio</h5>
                            <span><img src="assets/img/icons/icon-sq-ft.png" alt="">1012 sq ft</span>
                            <span><img src="assets/img/icons/icon-beach-view.png" alt="">Beach view</span>
                            <span><img src="assets/img/icons/icon-beds.png" alt="">2 Twin Beds</span>
                            <span><img src="assets/img/icons/icon-guests.png" alt="">4 Guests</span>
                            <span><img src="assets/img/icons/icon-welcome-drink.png" alt="">Welcome Drink</span>
                            <span><img src="assets/img/icons/icon-breakfast.png" alt="">Breakfast for 4</span>
                            <span><img src="assets/img/icons/icon-tea.png" alt="">Room Tea/Coffee</span>
                            <span><img src="assets/img/icons/icon-room.png" alt="">24 h room service</span>
                            <span><img src="assets/img/icons/icon-car.png" alt="">Free parking</span>
                            <span><img src="assets/img/icons/icon-air-condiitoner.png" alt="">Air Conditioner</span>
                        </div>
                        <div class="room-column-bottom">
                            <p>BDT 34,906 <span>/night</span></p>
                            <a href="" class="btn btn-bg">Reserve The Room</a>
                            <a href="" class="btn btn-border">Make An Offer</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="single-room-column">
                        <div class="room-column-slider">
                            <span class="room-column-badge">Most Popular</span>
                            <span class="room-column-icon">
                                <a href=""><img src="assets/img/icons/icon-heart.png" alt=""></a>
                            </span>
                            <div id="room-indicator-2" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-indicators">
                                    <button type="button" data-bs-target="#room-indicator-2" data-bs-slide-to="0" class="active" aria-label="Slide 1" aria-current="true"></button>
                                    <button type="button" data-bs-target="#room-indicator-2" data-bs-slide-to="1" aria-label="Slide 2" class=""></button>
                                    <button type="button" data-bs-target="#room-indicator-2" data-bs-slide-to="2" aria-label="Slide 3" class=""></button>
                                </div>
                                <div class="carousel-inner">
                                    <div class="carousel-item active">
                                    <img src="assets/img/rooms/room-1.png" class="d-block w-100" alt="...">
                                    </div>
                                    <div class="carousel-item">
                                    <img src="assets/img/rooms/room-2.png" class="d-block w-100" alt="...">
                                    </div>
                                    <div class="carousel-item">
                                    <img src="assets/img/rooms/room-3.png" class="d-block w-100" alt="...">
                                    </div>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#room-indicator-2" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true">
                                        <img src="assets/img/icons/arrow_back.png" alt="">
                                    </span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#room-indicator-2" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true">
                                        <img src="assets/img/icons/arrow_forward.png" alt="">
                                    </span>
                                </button>
                            </div>
                        </div>
                        <div class="room-column-content">
                            <h5>Superior Duplex Room</h5>
                            <span><img src="assets/img/icons/icon-sq-ft.png" alt="">1012 sq ft</span>
                            <span><img src="assets/img/icons/icon-beach-view.png" alt="">Beach view</span>
                            <span><img src="assets/img/icons/icon-beds.png" alt="">2 Twin Beds</span>
                            <span><img src="assets/img/icons/icon-guests.png" alt="">4 Guests</span>
                            <span><img src="assets/img/icons/icon-welcome-drink.png" alt="">Welcome Drink</span>
                            <span><img src="assets/img/icons/icon-breakfast.png" alt="">Breakfast for 4</span>
                            <span><img src="assets/img/icons/icon-tea.png" alt="">Room Tea/Coffee</span>
                            <span><img src="assets/img/icons/icon-room.png" alt="">24 h room service</span>
                            <span><img src="assets/img/icons/icon-car.png" alt="">Free parking</span>
                            <span><img src="assets/img/icons/icon-air-condiitoner.png" alt="">Air Conditioner</span>
                        </div>
                        <div class="room-column-bottom">
                            <p>BDT 40,900 <span>/night</span></p>
                            <a href="" class="btn btn-bg">Reserve The Room</a>
                            <a href="" class="btn btn-border">Make An Offer</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="single-room-column">
                        <div class="room-column-slider">
                            <span class="room-column-icon">
                                <a href=""><img src="assets/img/icons/icon-heart.png" alt=""></a>
                            </span>
                            <div id="room-indicator-3" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-indicators">
                                    <button type="button" data-bs-target="#room-indicator-3" data-bs-slide-to="0" class="active" aria-label="Slide 1" aria-current="true"></button>
                                    <button type="button" data-bs-target="#room-indicator-3" data-bs-slide-to="1" aria-label="Slide 2" class=""></button>
                                    <button type="button" data-bs-target="#room-indicator-3" data-bs-slide-to="2" aria-label="Slide 3" class=""></button>
                                </div>
                                <div class="carousel-inner">
                                    <div class="carousel-item active">
                                    <img src="assets/img/rooms/room-1.png" class="d-block w-100" alt="...">
                                    </div>
                                    <div class="carousel-item">
                                    <img src="assets/img/rooms/room-2.png" class="d-block w-100" alt="...">
                                    </div>
                                    <div class="carousel-item">
                                    <img src="assets/img/rooms/room-3.png" class="d-block w-100" alt="...">
                                    </div>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#room-indicator-3" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true">
                                        <img src="assets/img/icons/arrow_back.png" alt="">
                                    </span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#room-indicator-3" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true">
                                        <img src="assets/img/icons/arrow_forward.png" alt="">
                                    </span>
                                </button>
                            </div>
                        </div>
                        <div class="room-column-content">
                            <h5>Presidents Suite</h5>
                            <span><img src="assets/img/icons/icon-sq-ft.png" alt="">1012 sq ft</span>
                            <span><img src="assets/img/icons/icon-beach-view.png" alt="">Beach view</span>
                            <span><img src="assets/img/icons/icon-beds.png" alt="">2 Twin Beds</span>
                            <span><img src="assets/img/icons/icon-guests.png" alt="">4 Guests</span>
                            <span><img src="assets/img/icons/icon-welcome-drink.png" alt="">Welcome Drink</span>
                            <span><img src="assets/img/icons/icon-breakfast.png" alt="">Breakfast for 4</span>
                            <span><img src="assets/img/icons/icon-tea.png" alt="">Room Tea/Coffee</span>
                            <span><img src="assets/img/icons/icon-room.png" alt="">24 h room service</span>
                            <span><img src="assets/img/icons/icon-car.png" alt="">Free parking</span>
                            <span><img src="assets/img/icons/icon-air-condiitoner.png" alt="">Air Conditioner</span>
                        </div>
                        <div class="room-column-bottom">
                            <p>BDT 60,290 <span>/night</span></p>
                            <a href="" class="btn btn-bg">Reserve The Room</a>
                            <a href="" class="btn btn-border">Make An Offer</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="about-property">
        <div class="container">
            <div class="row about-property-box">
                <div class="col-xl-8">
                    <h4>About Property</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <img src="assets/img/logo-hotel.png" alt="">
                            <ul>
                                <li><span>Check-in time:</span> 1:00 PM</li>
                                <li><span>Check-out time:</span> 11:00 AM</li>
                            </ul>
                            <ul>
                                <li><img src="assets/img/icons/icon-phone.png" alt=""> 070 6302 8446 </li>
                                <li><img src="assets/img/icons/icon-map.png" alt=""> 2464 Royal Ln. Mesa, New Jersey 45463</li>
                            </ul>
                        </div>
                        <div class="col-xl-8">
                            <div class="property-content">
                                <h5><img src="assets/img/icon-star.png" alt=""> {{$property->property_class}} Hotel</h5>
                                <p>Amet minim mollit non deserunt ullamco est sit aliqua dolor do amet sint. Velit officia consequat duis enim velit mollit. Exercitation veniam consequat sunt nostrud amet.</p>
                                <p>Amet minim mollit non deserunt ullamco est sit aliqua dolor do amet sint. Velit officia consequat duis enim velit mollit.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 g-0">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d57903.13565556951!2d91.7892218563076!3d24.899824246801856!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x375054d3d270329f%3A0xf58ef93431f67382!2z4Ka44Ka_4Kay4KeH4Kaf!5e0!3m2!1sbn!2sbd!4v1679854607461!5m2!1sbn!2sbd"></iframe>
                </div>
            </div>
        </div>
    </section>
    <section class="reviews">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-lg-4">
                    <div class="review-summary d-flex align-items-center">
                        <h4>4.8</h4>
                        <p>Very Good <span>1234 reviews</span></p>
                    </div>
                    <div class="review-list">
                        <div class="review-item">
                            <div class="progress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 88%"></div>
                            </div>
                            <p>5 - Excellent</p>
                        </div>
                        <div class="review-item">
                            <div class="progress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 75%"></div>
                            </div>
                            <p>4 - Very Good</p>
                        </div>
                        <div class="review-item">
                            <div class="progress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 50%"></div>
                            </div>
                            <p>3 - Good</p>
                        </div>
                        <div class="review-item">
                            <div class="progress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 37%"></div>
                            </div>
                            <p>2 - Okay</p>
                        </div>
                        <div class="review-item">
                            <div class="progress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 12%"></div>
                            </div>
                            <p>1 - Poor</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 col-lg-8">
                    <h4 class="section-title">Reviews By Guests</h4>
                    <div class="single-review">
                        <div class="review-top d-flex justify-content-between align-items-end">
                            <div class="review-left d-flex align-items-start">
                                <div class="review-img">
                                    <img src="assets/img/client-1.png" alt="">
                                </div>
                                <div class="review-info">
                                    <h4>Brooklyn Simmons</h4>
                                    <p>4.8 / 5 - <span>Very Good</span></p>
                                </div>
                            </div>
                            <span class="review-date">Sep 27, 2016</span>
                        </div>
                        <p class="review-desc">“I ordered a Balmain T-shirt dress from here and it is fabulous. The packaging was extremely professional and nice and sweets were added in the box. Extremely sa...”</p>
                    </div>
                    <div class="single-review">
                        <div class="review-top d-flex justify-content-between align-items-end">
                            <div class="review-left d-flex align-items-start">
                                <div class="review-img">
                                    <img src="assets/img/client-2.png" alt="">
                                </div>
                                <div class="review-info">
                                    <h4>Arlene McCoy</h4>
                                    <p>4.8 / 5 - <span>Very Good</span></p>
                                </div>
                            </div>
                            <span class="review-date">Sep 27, 2016</span>
                        </div>
                        <p class="review-desc">"Great session! Dani was super helpful. She shared some practical advice on how can we go about refining our service offerings."</p>
                    </div>
                    <div class="single-review">
                        <div class="review-top d-flex justify-content-between align-items-end">
                            <div class="review-left d-flex align-items-start">
                                <div class="review-img">
                                    <img src="assets/img/client-3.png" alt="">
                                </div>
                                <div class="review-info">
                                    <h4>Jenny Wilson</h4>
                                    <p>4.8 / 5 - <span>Very Good</span></p>
                                </div>
                            </div>
                            <span class="review-date">Sep 27, 2016</span>
                        </div>
                        <p class="review-desc">“I tried the Hair mask first and its the best one I've ever tried love it. I've gone to buy the pamper packs for myself and as presents 🎁 could ot recommend en...”</p>
                    </div>
                    <div class="single-review">
                        <div class="review-top d-flex justify-content-between align-items-end">
                            <div class="review-left d-flex align-items-start">
                                <div class="review-img">
                                    <img src="assets/img/client-4.png" alt="">
                                </div>
                                <div class="review-info">
                                    <h4>Kristin Watson</h4>
                                    <p>4.8 / 5 - <span>Very Good</span></p>
                                </div>
                            </div>
                            <span class="review-date">Sep 27, 2016</span>
                        </div>
                        <p class="review-desc">"Hannah helped me cover a lot during a short time! I managed to get a good idea of how to set up and structure Facebook Ad Campaigns, how I should spend my clients budget wisely!"</p>
                    </div>
                    <div class="single-review">
                        <div class="review-top d-flex justify-content-between align-items-end">
                            <div class="review-left d-flex align-items-start">
                                <div class="review-img">
                                    <img src="assets/img/client-5.png" alt="">
                                </div>
                                <div class="review-info">
                                    <h4>Jacob Jones</h4>
                                    <p>4.8 / 5 - <span>Very Good</span></p>
                                </div>
                            </div>
                            <span class="review-date">Sep 27, 2016</span>
                        </div>
                        <p class="review-desc">"Whether you're totally new at Facebook ads or have some level of knowledge, Hannah is great at gauging where you're at and taking things from there. It's easy to keep up, and you'll definitely want to get in more time with someone as accomplished as her."</p>
                    </div>
                    <div class="review-loadbtn">
                        <a href="" class="btn btn-border">Load More</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="amenities">
        <div class="container">
            <div class="row text-center">
                <div class="col-12">
                    <h4 class="section-title">All Amenities</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="single-amenities">
                        <h5><img src="assets/img/icons/icon-wifi.png" alt=""> Internet</h5>
                        <span>Available in all rooms: Free WiFi</span>
                        <span>In-room WiFi speed: 25+ Mbps</span>
                    </div>
                    <div class="single-amenities">
                        <h5><img src="assets/img/icons/icon-room.png" alt=""> Room Service</h5>
                        <span>Change of bedsheets on request</span>
                        <span>Change of towels on request</span>
                        <span>Concierge services</span>
                        <span>Daily housekeeping</span>
                        <span>Dry cleaning/laundry service</span>
                        <span>Tour and ticket assistance</span>
                        <span>Turndown service</span>
                    </div>
                    <div class="single-amenities">
                        <h5><img src="assets/img/icons/icon-air-condiitoner.png" alt=""> Air Conditioner</h5>
                        <span>Well maintained central air conditioner</span>
                    </div>
                    <div class="single-amenities">
                        <h5><img src="assets/img/icons/icon-accesibility.png" alt=""> Airport Pick &amp; Drop</h5>
                        <span>AC car facility to pick up and drop from the airport.</span>
                        <span>Depending on room, it might be free or paid service.</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="single-amenities">
                        <h5><img src="assets/img/icons/icon-breakfast.png" alt=""> Free Breakfast</h5>
                        <span>Free buffet breakfast available daily 7:00 AM–10:30 AM</span>
                    </div>
                    <div class="single-amenities">
                        <h5><img src="assets/img/icons/icon-fitness-center.png" alt=""> Fitness Center</h5>
                        <span>Fitness classes</span>
                        <span>Gym</span>
                        <span>Yoga classes</span>
                    </div>
                    <div class="single-amenities">
                        <h5><img src="assets/img/icons/icon-accesibility.png" alt=""> Accessibility</h5>
                        <span>Braille/raised signage</span>
                        <span>Elevator</span>
                        <span>Grab bar near toilet</span>
                        <span>In-room accessibility (select rooms)</span>
                        <span>Valet for wheelchair-equipped vehicles</span>
                        <span>Wheelchair accessible (may have limitations)</span>
                        <span>Wheelchair-accessible pool</span>
                        <span>Wheelchair-accessible public washroom</span>
                        <span>Wheelchair-accessible registration desk</span>
                        <span>Wheelchair-accessible spa</span>
                    </div>
                    <div class="single-amenities">
                        <h5><img src="assets/img/icons/icon-entertainment.png" alt=""> Entertainment</h5>
                        <span>Flat-screen TV</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="single-amenities">
                        <h5><img src="assets/img/icons/icon-restaurant.png" alt=""> Restaurant</h5>
                        <span>2 bars/lounges</span>
                        <span>4 restaurants</span>
                        <span>A coffee shop/cafe</span>
                        <span>A poolside bar</span>
                        <span>Coffee and tea in common areas</span>
                    </div>
                    <div class="single-amenities">
                        <h5><img src="assets/img/icons/icon-car.png" alt=""> Car Parking</h5>
                        <span>Free private parking is possible on site (reservation is needed).</span>
                        <span>Secured parking</span>
                    </div>
                    <div class="single-amenities">
                        <h5><img src="assets/img/icons/icon-swimming-pool.png" alt=""> Swimming Pool</h5>
                        <span>3 outdoor pools</span>
                        <span>Spa tub</span>
                    </div>
                    <div class="single-amenities">
                        <h5><img src="assets/img/icons/icon-outdoor.png" alt=""> Outdoor</h5>
                        <span>BBQ facilities (Additional charge)</span>
                    </div>
                    <div class="single-amenities">
                        <h5><img src="assets/img/icons/icon-spa.png" alt=""> Spa &amp; Massage</h5>
                        <span>Steam room</span>
                        <span>Swedish massages</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="policies">
        <div class="container">
            <div class="row text-center">
                <div class="col-12">
                    <h4 class="section-title">Property Policies</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="single-policy">
                        <h4>Check-in</h4>
                        <span>Check-in from 2 PM - midnight</span>
                        <span>Late check-in subject to availability</span>
                        <span>Express check-in available</span>
                        <span>Minimum check-in age - 18</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="single-policy">
                        <h4>Check-out</h4>
                        <span>Check-out before noon</span>
                        <span>Express check-out available</span>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="single-policy">
                        <h4>Check-in instructions</h4>
                        <span>Guests must contact the property in advance for check-in instructions; front desk staff will greet guests on arrival</span>
                        <span>To make arrangements for check-in please contact the property ahead of time using the information on the booking confirmation</span>
                        <span>If you are planning to arrive after 6:00 PM please contact the property in advance using the information on the booking confirmation</span>
                        <span>Access via Staffs front desk</span>
                        <span>Pets not allowed</span>
                        <span>Children are welcome</span>
                        <span>Rollaway/extra beds are available for BDT 1900.0 per day</span>
                    </div>
                    <div class="single-policy">
                        <h4>Payment types</h4>
                        <div class="policy-card">
                            <img src="assets/img/visa-card.png" alt="">
                            <img src="assets/img/express-card.png" alt="">
                            <img src="assets/img/master-card.png" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-portal.layout>