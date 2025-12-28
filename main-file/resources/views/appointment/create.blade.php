{{ Form::open(['url' => 'appointment', 'method' => 'post', 'data-url' => route('appointment.duration'), 'id' => 'appointment-form-date','class'=>'needs-validation','novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('service', __('Service'), ['class' => 'form-label']) }}
                {{ Form::select('service', $service, null, ['class' => 'form-control service', 'required' => 'required', 'id' => 'service']) }}
                @permission('business update')
                    <div class=" text-xs mt-1">{{ __('Create service here. ') }}
                        <a href="{{ route('manage.business') }}"><b>{{ __('Create service') }}</b></a>
                    </div>
                @endpermission
                @error('service')
                    <small class="invalid-service" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </small>
                @enderror
            </div>
        </div>
        <div class="col-md-12 appoinment-customer-info">
            <div class="form-group">
                {{ Form::label('customer', __('Customer'), ['class' => 'form-label']) }}
                @stack('customer_booking')
                {{ Form::select('customer', $customer, null, ['class' => 'form-control', 'required' => 'required']) }}
                @permission('customer manage')
                    <div class=" text-xs mt-1">{{ __('Create customer here. ') }}
                        <a href="{{ route('customer.index') }}"><b>{{ __('Create customer') }}</b></a>
                    </div>
                @endpermission
                @error('customer')
                    <small class="invalid-customer" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </small>
                @enderror
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('location', __('Location'), ['class' => 'form-label']) }}
                {{ Form::select('location', $location, null, ['class' => 'form-control', 'required' => 'required']) }}
                @permission('business update')
                    <div class=" text-xs mt-1">{{ __('Create location here. ') }}
                        <a href="{{ route('manage.business') }}"><b>{{ __('Create location') }}</b></a>
                    </div>
                @endpermission
                @error('location')
                    <small class="invalid-location" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </small>
                @enderror
            </div>
        </div>


        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('staff', __('Staff'), ['class' => 'form-label']) }}
                {{ Form::select('staff', $staff, null, ['class' => 'form-control', 'required' => 'required', 'id' => 'staff']) }}
                @permission('business update')
                    <div class=" text-xs mt-1">{{ __('Create staff here. ') }}
                        <a href="{{ route('manage.business') }}"><b>{{ __('Create staff') }}</b></a>
                    </div>
                @endpermission
                @error('staff')
                    <small class="invalid-staff" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </small>
                @enderror
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('notes', __('Notes'), ['class' => 'form-label']) }}
                {{ Form::textarea('notes', null, ['class' => 'form-control', 'placeholder' => __('Enter notes'), 'rows' => '4']) }}
                @error('notes')
                    <small class="invalid-notes" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </small>
                @enderror
            </div>
        </div>
        {!! Form::hidden('appointment_status', 'Pending') !!}
        <div class="form-group col-md-6">
            <label for="appointment_date" class="col-form-label pt-0">{{ __('Appointment Date') }}</label>
            <div class="input-group date ">
                <input class="form-control datepicker p-2 px-3" type="text" id="datepicker" name="appointment_date" placeholder="DD-MM-YYYY"
                    autocomplete="off" required="required" data-dates={{ json_encode($combinedArray) }}
                    data-holiday={{ json_encode($businesholiday) }}>
                <span class="input-group-text">
                    <i class="feather icon-calendar"></i>
                </span>
            </div>
        </div>
        <div id="timeSlotsContainer"></div>
        @stack('setting_setup')
    </div>
    <div class="modal-footer p-0 pt-3 gap-3">
        <button type="button" class="btn m-0  btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        {{ Form::submit(__('Create'), ['class' => 'btn m-0 btn-primary']) }}
    </div>
    {{ Form::close() }}
</div>
