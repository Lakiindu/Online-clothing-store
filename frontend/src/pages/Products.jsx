import { useEffect, useState } from "react";
import { api } from "../api/http";

export default function Products() {
  const [products, setProducts] = useState([]);
  const [error, setError] = useState("");

  useEffect(() => {
    api("/products")
      .then((res) => setProducts(res.data))
      .catch(() => setError("Failed to load products"));
  }, []);

  return (
    <div>
      <h2>Products</h2>

      {error && <p style={{ color: "red" }}>{error}</p>}

      {products.map((p) => (
        <div
          key={p.id}
          style={{
            border: "1px solid #ccc",
            padding: 10,
            marginBottom: 10,
          }}
        >
          <h3>{p.name}</h3>
          <p>{p.description}</p>
          <strong>Rs. {p.price}</strong>
        </div>
      ))}
    </div>
  );
}
