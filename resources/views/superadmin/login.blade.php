<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restricted Access</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-900 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md bg-slate-800 rounded-lg shadow-2xl border border-slate-700 p-8">
        <div class="text-center mb-8">
            <svg class="w-12 h-12 text-red-600 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"></path></svg>
            <h2 class="text-2xl font-black text-white tracking-widest">RESTRICTED AREA</h2>
            <p class="text-slate-400 text-sm mt-2">Super Admin Authorization Required</p>
        </div>

        <form action="{{ route('superadmin.login.submit') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-300">Master Username</label>
                <input type="text" name="username" class="mt-1 block w-full bg-slate-900 border border-slate-600 rounded-md shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-red-500 focus:border-red-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-300">Passcode</label>
                <input type="password" name="password" class="mt-1 block w-full bg-slate-900 border border-slate-600 rounded-md shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-red-500 focus:border-red-500" required>
            </div>
            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 focus:ring-offset-slate-900 transition uppercase tracking-wider">
                Authenticate
            </button>
        </form>
    </div>

</body>
</html>