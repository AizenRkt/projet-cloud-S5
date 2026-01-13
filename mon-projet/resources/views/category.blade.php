<!DOCTYPE html>
<html>
<head>
    <title>{{ $category->name }}</title>
</head>
<body>
    <h1>{{ $category->name }}</h1>
    <p><strong>Slug :</strong> {{ $category->slug }}</p>
    <p><strong>Description :</strong> {{ $category->description }}</p>
    <p><strong>Couleur :</strong>{{ $category->color }}</p>
</body>
</html>
