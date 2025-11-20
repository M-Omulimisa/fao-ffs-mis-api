 @include('layouts.header', $layoutsParams)



 <div class="container py-4 py-lg-5 my-4">
     <div class="row content">
         <div class="col-md-6">
             <div class="card border-0 shadow">
                 <div class="card-body">
                     <h2 class="h4 mb-2 mb-md-3">Creating account</h2>

                     <form method="POST" action="{{ route('m-register-post') }}">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                         <div class="row gx-4 gy-3">
                             <div class="col-sm-6">
                                 @include('components.input-text', [
                                     'name' => 'first_name',
                                     'label' => 'First name',
                                 ])
                             </div>
                             <div class="col-sm-6">
                                 @include('components.input-text', [
                                     'name' => 'last_name',
                                     'label' => 'Last name',
                                 ])
                             </div>

                             <div class="col-sm-12">
                                 @include('components.input-text', [
                                     'name' => 'phone_number',
                                     'label' => 'Phone number',
                                     'type' => 'tel',
                                 ])
                             </div>

                             <div class="col-sm-12">
                                 @include('components.input-text', [
                                     'name' => 'password',
                                     'label' => 'Password',
                                     'type' => 'password',
                                 ])
                             </div>


                             <div class="col-sm-12">
                                 @include('components.input-text', [
                                     'name' => 'password_1',
                                     'label' => 'Re-enter Password',
                                     'type' => 'password',
                                 ])
                             </div>



                             <div class="col-12 text-end">
                                 <button class="btn btn-primary" type="submit"><i class="ci-user me-2 ms-n1"></i>Sign
                                     Up</button>
                             </div>
                         </div>
                     </form>
                 </div>
             </div>
         </div>

     </div>
 </div>


 @include('layouts.footer', $layoutsParams)
