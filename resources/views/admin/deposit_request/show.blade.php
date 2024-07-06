@extends('admin_layouts.app')

@section('content')

<div class="row mt-4">
  <div class="col-lg-12">
    <div class="card">
      <!-- Card header -->
      
      <div class="card-body">
        <form action="{{ route('admin.player.updateStatus',$deposit->id) }}" method="POST">
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
                <input type="text" class="form-control" name="" value="{{ $deposit->userPayment->paymentType->name}}" readonly>
              </div>
            </div>

            <div class="col-md-6">
              <div class="input-group input-group-outline is-valid my-3">
                <label class="form-label">Amount</label>
                <input type="text" class="form-control" name="amount" value="{{ $deposit->amount }}" readonly>
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
<script>
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
</script>
@endsection