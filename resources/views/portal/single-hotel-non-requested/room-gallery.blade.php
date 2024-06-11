<section class="rooms-gallery">
    <div class="container">
       <div class="row rooms-gallery-border">
          <div class="col-md-6">
             <div class="single-room-gallery position-relative">
                <img src="{{asset('assets/portal/img/rooms/room-gallery-1.png')}}" class="w-100" alt="">
                <a href="" class="btn btn-border" data-bs-toggle="modal" data-bs-target="#galleryModal"><img src="{{asset('assets/portal/img/icons/icon-gallery.png')}}" alt=""> Show All Photos</a>
             </div>
          </div>
          <div class="col-md-6">
             <div class="row align-content-center flex-wrap">
                <div class="col-md-6">
                   <img src="{{asset('assets/portal/img/rooms/room-gallery-2.png')}}" class="w-100 position-relative" alt="">
                </div>
                <div class="col-md-6">
                   <img src="{{asset('assets/portal/img/rooms/room-gallery-3.png')}}" class="w-100" alt="">
                </div>
                <div class="col-md-6 mt-4">
                   <img src="{{asset('assets/portal/img/rooms/room-gallery-4.png')}}" class="w-100" alt="">
                </div>
                <div class="col-md-6 mt-4">
                   <img src="{{asset('assets/portal/img/rooms/room-gallery-5.png')}}" class="w-100" alt="">
                </div>
             </div>
          </div>
       </div>
    </div>
 </section>

 <!-- Gallery  -->
 <div class="modal fade" id="galleryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
       <div class="modal-content">
          <div class="modal-body">
             <div id="carouselExample" class="carousel slide">
                <div class="carousel-inner">
                   <div class="carousel-item active">
                      <img src="{{asset('assets/portal/img/rooms/room-gallery-1.png')}}" class="d-block w-100" alt="...">
                   </div>
                   <div class="carousel-item">
                      <img src="{{asset('assets/portal/img/rooms/room-gallery-1.png')}}" class="d-block w-100" alt="...">
                   </div>
                   <div class="carousel-item">
                      <img src="{{asset('assets/portal/img/rooms/room-gallery-1.png')}}" class="d-block w-100" alt="...">
                   </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true">
                <img src="{{asset('assets/portal/img/icons/arrow_back.png')}}" alt="">
                </span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true">
                <img src="{{asset('assets/portal/img/icons/arrow_forward.png')}}" alt="">
                </span>
                </button>
             </div>
          </div>
       </div>
    </div>
 </div>

