<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profile</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f8;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 700px;
      margin: 40px auto;
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 6px 10px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 25px;
      color: #222;
    }
    .form-group {
      margin-bottom: 15px;
    }
    label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      color: #333;
    }
    input, select, textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 15px;
    }
    textarea {
      height: 80px;
      resize: vertical;
    }
    .btn {
      padding: 10px 20px;
      background: #ffcc00;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      transition: 0.3s;
    }
    .btn:hover {
      background: #e6b800;
    }
    .back-link {
      display: inline-block;
      margin-bottom: 20px;
      color: #007bff;
      text-decoration: none;
    }
    .back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="container">
  <a href="{{ route('profile.show') }}" class="back-link">← Back to Profile</a>
  <h2>Edit Profile</h2>

  @if($errors->any())
    <div class="alert alert-danger" style="background:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:20px;">
      <ul style="margin:0; padding-left:20px;">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form action="{{ route('profile.update') }}" method="POST">
    @csrf
    @method('PUT')

    <div class="form-group">
      <label for="name">Full Name</label>
      <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
    </div>

    <div class="form-group">
      <label for="phone">Phone</label>
      <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
    </div>

    <div class="form-group">
      <label for="sex">Gender</label>
      <select id="sex" name="sex">
        <option value="">Select</option>
        <option value="male" {{ old('sex', $user->sex) == 'male' ? 'selected' : '' }}>Male</option>
        <option value="female" {{ old('sex', $user->sex) == 'female' ? 'selected' : '' }}>Female</option>
        <option value="other" {{ old('sex', $user->sex) == 'other' ? 'selected' : '' }}>Other</option>
      </select>
    </div>

    <div class="form-group">
      <label for="dob">Date of Birth</label>
      <input type="date" id="dob" name="dob" value="{{ old('dob', $user->dob) }}">
    </div>

    <div class="form-group">
      <label for="default_address">Default Address</label>
      <textarea id="default_address" name="default_address">{{ old('default_address', $user->default_address) }}</textarea>
    </div>

    <div class="form-group">
      <label for="billing_address">Billing Address</label>
      <textarea id="billing_address" name="billing_address">{{ old('billing_address', $user->billing_address) }}</textarea>
    </div>

    <button type="submit" class="btn">Save Changes</button>
  </form>
</div>

</body>
</html>