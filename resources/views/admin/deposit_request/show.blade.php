@extends('admin_layouts.app')
@section('styles')
<style>
  .transparent-btn {
    background: none;
    border: none;
    padding: 0;
    outline: none;
    cursor: pointer;
    box-shadow: none;
    appearance: none;
    /* For some browsers */
  }


  .custom-form-group {
    margin-bottom: 20px;
  }

  .custom-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #555;
  }

  .custom-form-group input,
  .custom-form-group select {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #e1e1e1;
    border-radius: 5px;
    font-size: 16px;
    color: #333;
  }

  .custom-form-group input:focus,
  .custom-form-group select:focus {
    border-color: #d33a9e;
    box-shadow: 0 0 5px rgba(211, 58, 158, 0.5);
  }

  .submit-btn {
    background-color: #d33a9e;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 18px;
    font-weight: bold;
  }

  .submit-btn:hover {
    background-color: #b8328b;
  }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/material-icons@1.13.12/iconfont/material-icons.min.css">
@endsection
@section('content')

<div class="row mt-4">
  <div class="col-lg-12">
    <div class="card">
      <!-- Card header -->
      
      <div class="card-body">
        <form action="{{ route('admin.agent.updateStatus',$deposit->id) }}" method="POST">
          @csrf
          <div class="row">
          <input type="hidden" class="form-control" name="player" value="{{ $deposit->user->id }}" readonly>

            <div class="col-md-6">
              <div class="input-group input-group-outline is-valid my-3">
                <label class="form-label">User Name</label>
                <input type="text" class="form-control" name="name" value="{{ $deposit->user->name }}" readonly>

              </div>
              @error('name')
              <span class="d-block text-danger">*{{ $message }}</span>
              @enderror
            </div>
            <div class="col-md-6">
              <div class="input-group input-group-outline is-valid my-3">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" name="phone" value="{{ $deposit->user->phone }}" readonly>

              </div>
            </div>

            <div class="col-md-6">
              <div class="input-group input-group-outline is-valid my-3">
                <label class="form-label">Account Name</label>
                <input type="text" class="form-control" name="account_name" value="{{ $deposit->userPayment->account_name }}" readonly>

              </div>
            </div>

            <div class="col-md-6">
              <div class="input-group input-group-outline is-valid my-3">
                <label class="form-label">Account No</label>
                <input type="text" class="form-control" name="account_no" value="{{ $deposit->userPayment->account_no }}" readonly>
              </div>
            </div>

            <div class="col-md-6">
              <div class="input-group input-group-outline is-valid my-3">
                <label class="form-label">Payment Method</label>
                <input type="text" class="form-control" name="payment_method" value="{{ $deposit->userPayment->paymentType->name}}" readonly>
              </div>
            </div>

            <div class="col-md-6">
              <div class="input-group input-group-outline is-valid my-3">
                <label class="form-label">Amount</label>
                <input type="number" class="form-control" name="amount" value="{{ $deposit->amount }}" readonly>
              </div>
            </div>

            <div class="col-md-6">
              <div class="input-group input-group-outline is-valid my-3">
                <label class="form-label">Bonus</label>
                <input type="number" class="form-control" name="bonus">
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="input-group input-group-outline is-valid my-3">
                <select name="status" id="" class="form-control">
                <option value="0" {{ $deposit->status == 0 ? 'selected' : '' }}>Pending</option>
                <option value="1" {{ $deposit->status == 1 ? 'selected' : '' }}>Approved</option>
                <option value="2" {{ $deposit->status == 2 ? 'selected' : '' }}>Rejected</option>

                </select>
                @error('status')
              <span class="d-block text-danger">*{{ $message }}</span>
              @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="input-group input-group-outline is-valid my-3">
                <label class="form-label">RefrenceNo</label>
                <input type="text" class="form-control" name="refrence_no" value="{{ $deposit->refrence_no }}" readonly>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="input-group input-group-outline is-valid my-3">
                <button type="submit" class="btn btn-primary">update</button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>

<script src="{{ asset('admin_app/assets/js/plugins/choices.min.js') }}"></script>
<script src="{{ asset('admin_app/assets/js/plugins/quill.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

{{-- <script>
  document.addEventListener('DOMContentLoaded', function() {
    var errorMessage =  @json(session('error'));
    var successMessage =  @json(session('success'));
    @if(session()->has('success'))
    Swal.fire({
      icon: 'success',
      title: successMessage,
      text: '{{ session('
      SuccessRequest ') }}',
      timer: 3000,
      showConfirmButton: false
    });
    @elseif(session()->has('error'))
    Swal.fire({
      icon: 'error',
      title: '',
      text: errorMessage,
      timer: 3000,
      showConfirmButton: false
    });
    @endif
  });
</script> --}}

<script>
  var errorMessage = @json(session('error'));
  var successMessage = @json(session('success'));
  var url = 'https://win99mm.com/login';
  var player = @json(session('player'));
  var name = @json(session('name'));
  var phone = @json(session('phone'));
  var account_name = @json(session('account_name'));
  var account_no = @json(session('account_no'));
  var payment_method = @json(session('payment_method'));
  var amount = @json(session('amount'));
  var bonus = @json(session('bonus'));
  //console.log(user_name);

  @if(session()->has('success'))
  Swal.fire({
    title: successMessage,
    icon: "success",
    showConfirmButton: false,
    showCloseButton: true,
    html: `
  <table class="table table-bordered" style="background:#eee;">
  <tbody>
  <tr>
    <td>AccoundID</td>
    <td id="tuser_name"> ${player}</td>
  </tr>
  <tr>
    <td>name</td>
    <td id="tname"> ${name}</td>
  </tr>
    <tr>
    <td>Ph</td>
    <td id="tphone"> ${phone}</td>
  </tr>
    <tr>
    <td>account_name</td>
    <td id="taccount_name"> ${account_name}</td>
  </tr>
    <tr>
    <td>account_no</td>
    <td id="taccount_no"> ${account_no}</td>
  </tr>
    <tr>
    <td>pay method</td>
    <td id="tpaymethod"> ${payment_method}</td>
  </tr>
    <tr>
    <td>DepositAmount</td>
    <td id="tmaount"> ${amount}</td>
  </tr>
    <tr>
    <td>Bonus</td>
    <td id="tbonus"> ${bonus}</td>
  </tr>
  <tr>
    <td>url</td>
    <td id=""> ${url}</td>
  </tr>
  <tr>
    <td></td>
    <td><a href="#" onclick="copy()" class="btn btn-sm btn-primary">copy</a></td>
  </tr>
 </tbody>
  </table>
  `
  });
  @elseif(session()->has('error'))
  Swal.fire({
    icon: 'error',
    title: errorMessage,
    showConfirmButton: false,
    timer: 1500
  })
  @endif
  function copy() {
      var user_name= $('#tuser_name').text();
      var user_name= $('#tname').text();
      var password= $('#tphone').text();
      var user_name= $('#taccount_name').text();
      var user_name= $('#taccount_no').text();
      var user_name= $('#tpaymethod').text();
      var user_name= $('#tamount').text();
      var user_name= $('#tbonus').text();

      var copy = "url : "+url+"\nuser_name : "+player+"\nname : "+name+ "\nphone" +phone+ "\naccount_name : " +account_name+ "\naccount_no" +account_no+ "\npayment method" +payment_method+ "n\depositAmount :" +amount+ "\nBonus : " +bonus;
        copyToClipboard(copy)
  }
  
  function copyToClipboard(v) {
            var $temp = $("<textarea>");
            $("body").append($temp);
            var html = v;
            $temp.val(html).select();
            document.execCommand("copy");
            $temp.remove();
        }

  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('resetFormButton').addEventListener('click', function () {
            var form = this.closest('form');
            form.querySelectorAll('input[type="text"]').forEach(input => {
                // Resets input fields to their default values
                input.value = '';
            });
            form.querySelectorAll('select').forEach(select => {
                // Resets select fields to their default selected option
                select.selectedIndex = 0;
            });
            // Add any additional field resets here if necessary
        });
    });
</script>

@endsection