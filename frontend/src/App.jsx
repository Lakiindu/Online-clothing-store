import Products from "./pages/Products";
import Login from "./pages/Login";

export default function App() {
  return (
    <div style={{ padding: 20 }}>
      <h1>Online Clothing Store</h1>

      <Login />

      <hr style={{ margin: "20px 0" }} />

      <Products />
    </div>
  );
}
