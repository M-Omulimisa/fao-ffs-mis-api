<style>
    .picker-item {
        border: solid 2px #6A3A00;
        padding: 1.5rem;
        border-radius: 1.5rem;
        margin-top: 2rem;
        font-size: 2rem;
        color: #120b02;
    }

    .picker-item .title {
        font-weight: 800;
        font-size: 3rem;
    }

    .picker-item:hover {
        background-color: #6A3A00;
        color: white;

    }
</style> 


<h3 style="font-weight:800; color: black;">Choose your role</h3>
<hr>
<p style="font-size: 2rem;">Thank you for creating your account. Now let us know the role you want to play in this
    system. Please carefully pick your
    role below to proceed.</p>



<a href="{{ admin_url('become-farmer') }}">
    <div class="picker-item">
        <h4 class="title">Farmer</h4>
        <p>To register your farms, livestock, livestock events and application for movement permits.</p>
    </div>
</a>


<a href="{{ admin_url('form-drug-sellers/create') }}">
    <div class="picker-item">
        <h4 class="title">Drug distributor</h4>
        <p>To register your veterinary lisence, drug stocks, distribution and operations with <b>Uganda National Drug
                Authority.</b></p>
    </div>
</a>
