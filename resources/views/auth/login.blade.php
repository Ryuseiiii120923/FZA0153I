<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="icon" href="{{ asset('fuji_logo.ico') }}" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
<section class="bg-gray-50">
    <div class="flex flex-col items-center justify-center px-6 py-8 mx-auto md:h-screen lg:py-0">
        <a href="/" class="flex items-center mb-6 text-2xl font-semibold text-gray-900">
            <img class="w-[200px] h-auto mr-2" src="{{ asset('images/fuji_logo.png') }}" alt="logo">
        </a>
        <div class="w-full bg-white rounded-lg shadow md:mt-0 sm:max-w-md xl:p-0">
            <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                <h1 class="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl">
                    Sign in to your account
                </h1>

                <form class="space-y-4 md:space-y-6" action="{{ route('login.post') }}" method="POST">

                    @csrf

                    <div>
                        <label for="userid" class="block mb-2 text-sm font-medium text-gray-900">Your Employee Id</label>
                        <input type="text" name="userid" id="userid" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="xxxxxx" value="{{ old('userid') }}" required>
                        @error('userid')    
                        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block mb-2 text-sm font-medium text-gray-900">Password</label>
                        <input type="password" name="password" id="password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                        @error('password')
                        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full text-white hover:bg-blue-700 bg-blue-600 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Sign in</button>

                    @if($errors->any())
                        <ul class="px-4 py-2 bg-red-100 mt-2 rounded">
                            @foreach($errors->all() as $error)
                                <li class="my-1 text-red-500">{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </form>
            </div>
        </div>
    </div>
</section>
</body>
</html>
