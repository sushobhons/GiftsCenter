<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Link</th>
        <th>Identifier Exists</th>
        <th>Price</th>
        <th>Image Link</th>
        <th>Brand</th>
    </tr>
    </thead>
    <tbody>
    @foreach($products as $product)
        <tr>
            <td>{{ $product->id }}</td>
            <td>{{ $product->title }}</td>
            <td>{{ $product->link }}</td>
            <td>{{ $product->identifier_exists }}</td>
            <td>{{ $product->price }}</td>
            <td>{{ $product->image_link }}</td>
            <td>{{ $product->brand }}</td>
        </tr>
    @endforeach
    </tbody>
</table>