<!DOCTYPE html>
<html>
<head>
    <title>Catégories</title>
</head>
<body>
    <h1>Liste des catégories</h1>
    <ul>
        @foreach($categories as $category)
            <li>
                <strong>{{ $category->name }}</strong> - {{ $category->slug }}<br>
                {{ $category->description }}
            </li>
        @endforeach
    </ul>
</body>
</html>
