<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <div class="container">
        <table border="1">
            <tr>
                <td>Username</td>
                <td>Email</td>
            </tr>
            @foreach ($data as $item)
            <tr>
                <td>{{ $item->username }}</td> 
                <td>{{ $item->email }}</td> 
            </tr>
            @endforeach
        </table>
    </div>
</body>
</html>