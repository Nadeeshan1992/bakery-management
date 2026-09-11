// mobile_app/lib/main.dart - Sales Person Mobile Application Entry Point
import 'package:flutter/material.dart';

void main() {
  runApp(const BakerySalesApp());
}

class BakerySalesApp extends StatelessWidget {
  const BakerySalesApp({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Bakery Sales App',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        primarySwatch: Colors.orange,
        scaffoldBackgroundColor: const Color(0xFFFDF8F5),
        fontFamily: 'Roboto',
      ),
      home: const SalesLoginScreen(),
    );
  }
}

class SalesLoginScreen extends StatefulWidget {
  const SalesLoginScreen({Key? key}) : super(key: key);

  @override
  State<SalesLoginScreen> createState() => _SalesLoginScreenState();
}

class _SalesLoginScreenState extends State<SalesLoginScreen> {
  final _usernameController = TextEditingController(text: 'sales');
  final _passwordController = TextEditingController(text: 'password123');

  void _login() {
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (context) => const SalesDashboardScreen()),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.orange.shade800,
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.bakery_dining, size: 64, color: Colors.white),
              ),
              const SizedBox(height: 16),
              const Text(
                'MLB Bakery Sales App',
                style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Color(0xFF8C4A27)),
              ),
              const Text(
                'Mobile Pre-Order & Credit System',
                style: TextStyle(color: Colors.grey),
              ),
              const SizedBox(height: 32),
              TextField(
                controller: _usernameController,
                decoration: const InputDecoration(
                  labelText: 'Username',
                  prefixIcon: Icon(Icons.person),
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: _passwordController,
                obscureText: true,
                decoration: const InputDecoration(
                  labelText: 'Password',
                  prefixIcon: Icon(Icons.lock),
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                height: 50,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFD97724),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  onPressed: _login,
                  child: const Text('Log In as Sales Person', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class SalesDashboardScreen extends StatelessWidget {
  const SalesDashboardScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Sales Person Dashboard'),
        backgroundColor: const Color(0xFFD97724),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Card(
              elevation: 2,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Row(
                  children: [
                    const CircleAvatar(
                      backgroundColor: Colors.orange,
                      child: Icon(Icons.person, color: Colors.white),
                    ),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: const [
                        Text('John Sales', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                        Text('Role: Sales Person', style: TextStyle(color: Colors.grey)),
                      ],
                    )
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),
            ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.amber.shade800,
                minimumSize: const Size(double.infinity, 50),
              ),
              onPressed: () {},
              icon: const Icon(Icons.add_shopping_cart),
              label: const Text('Book New Pre-Order', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      ),
    );
  }
}
