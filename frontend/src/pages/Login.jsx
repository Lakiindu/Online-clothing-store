import { useState } from "react";

export default function Login() {
  const [email, setEmail] = useState("lakindu@gmail.com");
  const [password, setPassword] = useState("lakindu");
  const [msg, setMsg] = useState("");

  async function handleLogin(e) {
    e.preventDefault();
    setMsg("Logging in...");

    try {
      const res = await fetch("http://localhost:8000/api/auth/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password }),
      });

      const json = await res.json();

      if (!res.ok || !json.ok) {
        setMsg(json.message || "Login failed");
        return;
      }

      const token = json.data?.token;
      if (!token) {
        setMsg("No token received");
        return;
      }

      localStorage.setItem("token", token);
      setMsg("✅ Logged in! Token saved to localStorage");
    } catch (err) {
      setMsg("Error: " + err.message);
    }
  }

  return (
    <div style={{ marginTop: 20 }}>
      <h2>Login</h2>

      <form onSubmit={handleLogin} style={{ display: "grid", gap: 10, maxWidth: 320 }}>
        <input
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          placeholder="email"
        />
        <input
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          type="password"
          placeholder="password"
        />
        <button type="submit">Login</button>
      </form>

      <p>{msg}</p>
    </div>
  );
}
