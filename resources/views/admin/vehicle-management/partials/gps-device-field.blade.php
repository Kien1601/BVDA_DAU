<x-form.input name="gps_device_id" label="Mã thiết bị (IMEI)" :value="$vehicle->gps_device_id"
              inputmode="numeric" placeholder="VD: 860000000000001"
              hint="Để trống nếu xe chưa gắn thiết bị. Xóa mã để gỡ thiết bị khỏi xe." />