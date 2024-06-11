@extends('portal.layout.master')
@section("title","property-add")
@section('content')
<section class="property-step">
    <div class="container">
       <!-- MultiStep Form -->
       <div class="row">
          <div class="col-xl-8 col-md-12 mx-auto">
             <form class="property-form">
                <!-- progressbar -->
                <ul id="progressbar">
                   <li class="active"> Step 01</li>
                   <li> Step 02</li>
                   <li> Step 03</li>
                </ul>
                <!-- fieldsets -->
                <fieldset>
                   <!-- step one -->
                   <div class="step">
                      <h4>Your property's address</h4>
                      <div class="single-step select-step">
                         <img src="assets/img/icons/step-forms/step-form-icon-1.svg" alt="">
                         <select>
                            <option value="">Select Country</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                         </select>
                      </div>
                      <div class="single-step">
                         <img src="assets/img/icons/step-forms/step-form-icon-2.svg" alt="">
                         <input type="text" placeholder="Type Your Street Address">
                      </div>
                      <div class="single-step">
                         <img src="assets/img/icons/step-forms/step-form-icon-3.svg" alt="">
                         <input type="text" placeholder="Your City">
                      </div>
                      <div class="row">
                         <div class="col">
                            <div class="single-step">
                               <img src="assets/img/icons/step-forms/step-form-icon-4.svg" alt="">
                               <input type="text" placeholder="State / Division">
                            </div>
                         </div>
                         <div class="col">
                            <div class="single-step">
                               <img src="assets/img/icons/step-forms/step-form-icon-5.svg" alt="">
                               <input type="text" placeholder="Area ZIP Code">
                            </div>
                         </div>
                      </div>
                   </div>
                   <input type="button" name="next" class="next action-button" value="Next">
                </fieldset>
                <fieldset>
                   <!-- step two -->
                   <div class="step">
                      <h4>Information About Your Property</h4>
                      <div class="single-step">
                         <img src="assets/img/icons/step-forms/step-form-icon-6.svg" alt="">
                         <input type="text" placeholder="Property Name">
                      </div>
                      <div class="single-step select-step">
                         <img src="assets/img/icons/step-forms/step-form-icon-7.svg" alt="">
                         <select>
                            <option value="">Select Property Type</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                         </select>
                      </div>
                      <div class="row">
                         <div class="col">
                            <div class="single-step">
                               <img src="assets/img/icons/step-forms/step-form-icon-8.svg" alt="">
                               <input type="text" placeholder="Mention Number Of Rooms">
                            </div>
                         </div>
                         <div class="col">
                            <div class="single-step select-step">
                               <img src="assets/img/icons/step-forms/step-form-icon-10.svg" alt="">
                               <select>
                                  <option value="">Accepted Currency</option>
                                  <option value="">Bangladesh</option>
                                  <option value="">Bangladesh</option>
                                  <option value="">Bangladesh</option>
                                  <option value="">Bangladesh</option>
                                  <option value="">Bangladesh</option>
                                  <option value="">Bangladesh</option>
                                  <option value="">Bangladesh</option>
                                  <option value="">Bangladesh</option>
                               </select>
                            </div>
                         </div>
                      </div>
                      <div class="single-step">
                         <p>Does this property work with a channel manager?</p>
                         <div class="single-step-radio">
                             <label>
                                 <input type="radio" name="radio2"><span>Yes</span>
                             </label>
                             <label>
                                 <input type="radio" name="radio2"><span>No</span>
                             </label>
                         </div>
                      </div>
                      <div class="single-step">
                         <p>Is this property part of a chain?</p>
                         <div class="single-step-radio">
                             <label>
                                 <input type="radio" name="radio2"><span>Yes</span>
                             </label>
                             <label>
                                 <input type="radio" name="radio2"><span>No</span>
                             </label>
                         </div>
                      </div>
                   </div>
                   <input type="button" name="next" class="next action-button" value="Next">
                   <input type="button" name="previous" class="previous action-button-previous" value="Cancel">
                </fieldset>
                <fieldset>
                   <!-- step three -->
                   <div class="step">
                      <h4>Your property's address</h4>
                      <div class="single-step">
                         <img src="assets/img/icons/step-forms/step-form-icon-10.svg" alt="">
                         <input type="text" placeholder="Property Owner Name">
                      </div>
                      <div class="row">
                         <div class="col-3">
                             <div class="single-step select-step">
                                 <img src="assets/img/icons/step-forms/step-form-icon-12.svg" alt="">
                                 <select>
                                     <option value="">+88</option>
                                     <option value="">Bangladesh</option>
                                     <option value="">Bangladesh</option>
                                     <option value="">Bangladesh</option>
                                     <option value="">Bangladesh</option>
                                     <option value="">Bangladesh</option>
                                     <option value="">Bangladesh</option>
                                     <option value="">Bangladesh</option>
                                     <option value="">Bangladesh</option>
                                 </select>
                             </div>
                         </div>
                         <div class="col-9">
                            <div class="single-step">
                               <img src="assets/img/icons/step-forms/step-form-icon-15.svg" alt="">
                               <input type="text" placeholder="Type Phone Number">
                            </div>
                         </div>
                      </div>
                      <div class="single-step">
                         <img src="assets/img/icons/step-forms/step-form-icon-13.svg" alt="">
                         <input type="text" placeholder="Type Your Email Address">
                      </div>
                      <div class="single-step">
                         <p>Upload scanned copies of the required legal documents.</p>
                         <div class="single-step-radio-image">
                             <div class="step-radio-image-box">
                                 <input type="radio" name="payment" id="trade">
                                 <label for="trade">
                                     <img src="assets/img/icons/step-forms/step-form-icon-14.svg" alt="">
                                     <span>Trade License</span>
                                 </label>
                             </div>
                             <div class="step-radio-image-box">
                                 <input type="radio" name="payment" id="tin">
                                 <label for="tin">
                                     <img src="assets/img/icons/step-forms/step-form-icon-14.svg" alt="">
                                     <span>Business TIN</span>
                                 </label>
                             </div>
                             <div class="step-radio-image-box">
                                 <input type="radio" name="payment" id="others">
                                 <label for="others">
                                     <img src="assets/img/icons/step-forms/step-form-icon-14.svg" alt="">
                                     <span>Others</span>
                                 </label>
                             </div>
                         </div>
                      </div>
                   </div>

                   <input type="button" name="submit" class="submit action-button" value="List Your Property" data-bs-toggle="modal" data-bs-target="#exampleModal">
                   <input type="button" name="previous" class="previous action-button-previous" value="Go Back">
                </fieldset>
             </form>
          </div>
       </div>
       <!-- /.MultiStep Form -->
    </div>
 </section>

    <!-- Model  -->
    <div class="modal fade property-add-modal" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-body text-center">
              <img src="assets/img/icons/icon-celebration.svg" alt="">
              <h4>Thank For Showing Interest To Grow <br> Business With Rate Locker</h4>
              <p>You request for add your property on rate locker is successfully completed. Very shortly our representative will contact with for setting up the system.</p>
              <p>In the mean time, you can explore our site.</p>
              <a href="">Back To Home ➡️</a>
            </div>
          </div>
        </div>
      </div>
@endsection
