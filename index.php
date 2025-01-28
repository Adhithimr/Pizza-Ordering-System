<?php

include 'config.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

if(isset($_POST['register'])){

   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_STRING);
   $pass = sha1($_POST['pass']);
   $pass = filter_var($pass, FILTER_SANITIZE_STRING);
   $cpass = sha1($_POST['cpass'] );
   $cpass = filter_var($cpass, FILTER_SANITIZE_STRING);

   $select_user = $conn->prepare("SELECT * FROM `user` WHERE name = ? AND email = ?");
   $select_user->execute([$name, $email]);

   if($select_user->rowCount() > 0){
      $message[] = 'username or email already exists!';
   }else{
      if($pass != $cpass){
         $message[] = 'confirm password not matched!';
      }else{
         $insert_user = $conn->prepare("INSERT INTO `user`(name, email, password) VALUES(?,?,?)");
         $insert_user->execute([$name, $email, $cpass]);
         $message[] = 'registered successfully, login now please!';
      }
   }

}

if(isset($_POST['update_qty'])){
   $cart_id = $_POST['cart_id'];
   $qty = $_POST['qty'];
   $qty = filter_var($qty, FILTER_SANITIZE_STRING);
   $update_qty = $conn->prepare("UPDATE `cart` SET quantity = ? WHERE id = ?");
   $update_qty->execute([$qty, $cart_id]);
   $message[] = 'cart quantity updated!';
}

if(isset($_GET['delete_cart_item'])){
   $delete_cart_id = $_GET['delete_cart_item'];
   $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE id = ?");
   $delete_cart_item->execute([$delete_cart_id]);
   header('location:index.php');
}

if(isset($_GET['logout'])){
   session_unset();
   session_destroy();
   header('location:index.php');
}

if(isset($_POST['add_to_cart'])){

   if($user_id == ''){
      $message[] = 'please login first!';
   }else{

      $pid = $_POST['pid'];
      $name = $_POST['name'];
      $price = $_POST['price'];
      $image = $_POST['image'];
      $qty = $_POST['qty'];
      $qty = filter_var($qty, FILTER_SANITIZE_STRING);

      $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ? AND name = ?");
      $select_cart->execute([$user_id, $name]);

      if($select_cart->rowCount() > 0){
         $message[] = 'already added to cart';
      }else{
         $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
         $insert_cart->execute([$user_id, $pid, $name, $price, $qty, $image]);
         $message[] = 'added to cart!';
      }

   }

}

if(isset($_POST['order'])){

   if($user_id == ''){
      $message[] = 'please login first!';
   }else{
      $name = $_POST['name'];
      $name = filter_var($name, FILTER_SANITIZE_STRING);
      $number = $_POST['number'];
      $number = filter_var($number, FILTER_SANITIZE_STRING);
      $address = 'flat no.'.$_POST['flat'].', '.$_POST['street'].' - '.$_POST['pin_code'];
      $address = filter_var($address, FILTER_SANITIZE_STRING);
      $method = $_POST['method'];
      $method = filter_var($method, FILTER_SANITIZE_STRING);
      $total_price = $_POST['total_price'];
      $total_products = $_POST['total_products'];

      $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
      $select_cart->execute([$user_id]);

      if($select_cart->rowCount() > 0){
         $insert_order = $conn->prepare("INSERT INTO `orders`(user_id, name, number, method, address, total_products, total_price) VALUES(?,?,?,?,?,?,?)");
         $insert_order->execute([$user_id, $name, $number, $method, $address, $total_products, $total_price]);
         $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
         $delete_cart->execute([$user_id]);
         $message[] = 'order placed successfully!';
      }else{
         $message[] = 'your cart empty!';
      }
   }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Pizza Ordering System</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>

<?php
   if(isset($message)){
      foreach($message as $message){
         echo '
         <div class="message">
            <span>'.$message.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
?>

<!-- header section starts  -->

<header class="header">

   <section class="flex">

      <a href="#home" class="logo"><span>P</span>izza.</a>

      <nav class="navbar">
         <a href="#home">home</a>
         <a href="#about">about</a>
         <a href="#menu">menu</a>
         <a href="#order">order</a>
         <a href="#faq">faq</a>
      </nav>

      <div class="icons">
         <div id="menu-btn" class="fas fa-bars"></div>
         <div id="user-btn" class="fas fa-user"></div>
         <div id="order-btn" class="fas fa-box"></div>
         <?php
            $count_cart_items = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $count_cart_items->execute([$user_id]);
            $total_cart_items = $count_cart_items->rowCount();
         ?>
         <div id="cart-btn" class="fas fa-shopping-cart"><span>(<?= $total_cart_items; ?>)</span></div>
      </div>

   </section>

</header>

<!-- header section ends -->

<div class="user-account">

   <section>

      <div id="close-account"><span>close</span></div>

      <div class="user">
         <?php
            $select_user = $conn->prepare("SELECT * FROM `user` WHERE id = ?");
            $select_user->execute([$user_id]);
            if($select_user->rowCount() > 0){
               while($fetch_user = $select_user->fetch(PDO::FETCH_ASSOC)){
                  echo '<p>welcome ! <span>'.$fetch_user['name'].'</span></p>';
                  echo '<a href="index.php?logout" class="btn">logout</a>';
               }
            }else{
               echo '<p><span>you are not logged in now!</span></p>';
            }
         ?>
      </div>

      <div class="display-orders">
         <?php
            $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $select_cart->execute([$user_id]);
            if($select_cart->rowCount() > 0){
               while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
                  echo '<p>'.$fetch_cart['name'].' <span>('.$fetch_cart['price'].' x '.$fetch_cart['quantity'].')</span></p>';
               }
            }else{
               echo '<p><span>your cart is empty!</span></p>';
            }
         ?>
      </div>

      <div class="flex">

         <form action="user_login.php" method="post">
            <h3>login now</h3>
            <input type="email" name="email" required class="box" placeholder="enter your email" maxlength="50">
            <input type="password" name="pass" required class="box" placeholder="enter your password" maxlength="20">
            <input type="submit" value="login now" name="login" class="btn">
         </form>

         <form action="" method="post">
            <h3>register now</h3>
            <input type="text" name="name" oninput="this.value = this.value.replace(/\s/g, '')" required class="box" placeholder="enter your username" maxlength="20">
            <input type="email" name="email" required class="box" placeholder="enter your email" maxlength="50">
            <input type="password" name="pass" required class="box" placeholder="enter your password" maxlength="20" oninput="this.value = this.value.replace(/\s/g, '')">
            <input type="password" name="cpass" required class="box" placeholder="confirm your password" maxlength="20" oninput="this.value = this.value.replace(/\s/g, '')">
            <input type="submit" value="register now" name="register" class="btn">
         </form>

      </div>

   </section>

</div>

<div class="my-orders">

   <section>

      <div id="close-orders"><span>close</span></div>

      <h3 class="title"> my orders </h3>

      <?php
         $select_orders = $conn->prepare("SELECT * FROM `orders` WHERE user_id = ?");
         $select_orders->execute([$user_id]);
         if($select_orders->rowCount() > 0){
            while($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)){   
      ?>
      <div class="box">
         <p> placed on : <span><?= $fetch_orders['placed_on']; ?></span> </p>
         <p> name : <span><?= $fetch_orders['name']; ?></span> </p>
         <p> number : <span><?= $fetch_orders['number']; ?></span> </p>
         <p> address : <span><?= $fetch_orders['address']; ?></span> </p>
         <p> payment method : <span><?= $fetch_orders['method']; ?></span> </p>
         <p> total_orders : <span><?= $fetch_orders['total_products']; ?></span> </p>
         <p> total price : <span>$<?= $fetch_orders['total_price']; ?>/-</span> </p>
         <p> payment status : <span style="color:<?php if($fetch_orders['payment_status'] == 'pending'){ echo 'red'; }else{ echo 'green'; }; ?>"><?= $fetch_orders['payment_status']; ?></span> </p>
      </div>
      <?php
         }
      }else{
         echo '<p class="empty">nothing ordered yet!</p>';
      }
      ?>

   </section>

</div>

<div class="shopping-cart">

   <section>

      <div id="close-cart"><span>close</span></div>

      <?php
         $grand_total = 0;
         $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
         $select_cart->execute([$user_id]);
         if($select_cart->rowCount() > 0){
            while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
              $sub_total = ($fetch_cart['price'] * $fetch_cart['quantity']);
              $grand_total += $sub_total; 
      ?>
      <div class="box">
         <a href="index.php?delete_cart_item=<?= $fetch_cart['id']; ?>" class="fas fa-times" onclick="return confirm('delete this cart item?');"></a>
         <img src="uploaded_img/<?= $fetch_cart['image']; ?>" alt="">
         <div class="content">
          <p> <?= $fetch_cart['name']; ?> <span>(<?= $fetch_cart['price']; ?> x <?= $fetch_cart['quantity']; ?>)</span></p>
          <form action="" method="post">
             <input type="hidden" name="cart_id" value="<?= $fetch_cart['id']; ?>">
             <input type="number" name="qty" class="qty" min="1" max="99" value="<?= $fetch_cart['quantity']; ?>" onkeypress="if(this.value.length == 2) return false;">
               <button type="submit" class="fas fa-edit" name="update_qty"></button>
          </form>
         </div>
      </div>
      <?php
         }
      }else{
         echo '<p class="empty"><span>your cart is empty!</span></p>';
      }
      ?>

      <div class="cart-total"> grand total : <span>Rs<?= $grand_total; ?>/-</span></div>

      <a href="#order" class="btn">order now</a>

   </section>

</div>

<div class="home-bg">

   <section class="home" id="home">

   <link rel="stylesheet" type="text/css" href="css/style1.css">
   <main>
		
<h1>Life happens,pizza helps!! <span class="change_content"> 
</span> <span style="margin-top: -5px;">  </span> </h1>
	</main>


   </section>

</div>

<!-- about section starts  -->

<section class="about" id="about">

   <h1 class="heading">about us</h1>

   <div class="box-container">

      <div class="box">
         <img src="images/madewithlove.jpg" alt="">
         <h3>made with love</h3>
         <p>The pizza is made of dough and also need salt, sugar, yeast and water. All it mixed creates an elastic dough that can be molded in many different shapes, usually is a round, square, or a rectangle. That can be cuttet in triangles or small squares.</p>
         <a href="#menu" class="btn">our menu</a>
      </div>

      <div class="box">
         <img src="images/pizzadeliveryman.png" alt="">
         <h3>30 minutes delivery</h3>
         <p>The pizza chains deliver pizzas in 20-30 min after receiving an order.However,the delivery time is based on a few factors which include:Whether there’s a driver available to deliver the pizza.The distance between store and customer’s house.
         </p>
         <a href="#menu" class="btn">our menu</a>
      </div>

      <div class="box">
         <img src="images/sharingpizza.webp" alt="">
         <h3>share with freinds</h3>
         <p>One important thing to accompany pizza is a good soda or wine, I usually ask for coke or pepsi to eat at home or a pinot noir in a fancy place. ,any people think that water is better. </p>
         <a href="#menu" class="btn">our menu</a>
      </div>

   </div>

</section>

<!-- about section ends -->

<!-- menu section starts  -->

<section id="menu" class="menu">

   <h1 class="heading">our menu</h1>

   <div class="box-container">

      <?php
         $select_products = $conn->prepare("SELECT * FROM `products`");
         $select_products->execute();
         if($select_products->rowCount() > 0){
            while($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)){    
      ?>
      <div class="box">
         <div class="price">Rs<?= $fetch_products['price'] ?>/-</div>
         <img src="uploaded_img/<?= $fetch_products['image'] ?>" alt="">
         <div class="name"><?= $fetch_products['name'] ?></div>
         <form action="" method="post">
            <input type="hidden" name="pid" value="<?= $fetch_products['id'] ?>">
            <input type="hidden" name="name" value="<?= $fetch_products['name'] ?>">
            <input type="hidden" name="price" value="<?= $fetch_products['price'] ?>">
            <input type="hidden" name="image" value="<?= $fetch_products['image'] ?>">
            <input type="number" name="qty" class="qty" min="1" max="99" onkeypress="if(this.value.length == 2) return false;" value="1">
            <input type="submit" class="btn" name="add_to_cart" value="add to cart">
         </form>
      </div>
      <?php
         }
      }else{
         echo '<p class="empty">no products added yet!</p>';
      }
      ?>

   </div>

</section>

<!-- menu section ends -->

<!-- order section starts  -->

<section class="order" id="order">

   <h1 class="heading">order now</h1>

   <form action="" method="post">

   <div class="display-orders">

   <?php
         $grand_total = 0;
         $cart_item[] = '';
         $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
         $select_cart->execute([$user_id]);
         if($select_cart->rowCount() > 0){
            while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
              $sub_total = ($fetch_cart['price'] * $fetch_cart['quantity']);
              $grand_total += $sub_total; 
              $cart_item[] = $fetch_cart['name'].' ( '.$fetch_cart['price'].' x '.$fetch_cart['quantity'].' ) - ';
              $total_products = implode($cart_item);
              echo '<p>'.$fetch_cart['name'].' <span>('.$fetch_cart['price'].' x '.$fetch_cart['quantity'].')</span></p>';
            }
         }else{
            echo '<p class="empty"><span>your cart is empty!</span></p>';
         }
      ?>

   </div>

      <div class="grand-total"> grand total : <span>Rs<?= $grand_total; ?>/-</span></div>

      <input type="hidden" name="total_products" value="<?= $total_products; ?>">
      <input type="hidden" name="total_price" value="<?= $grand_total; ?>">

      <div class="flex">
         <div class="inputBox">
            <span>your name :</span>
            <input type="text" name="name" class="box" required placeholder="enter your name" maxlength="20">
         </div>
         <div class="inputBox">
            <span>your number :</span>
            <input type="number" name="number" class="box" required placeholder="enter your number" min="0" max="9999999999" onkeypress="if(this.value.length == 10) return false;">
         </div>
         <div class="inputBox">
            <span>payment method</span>
            <select name="method" class="box">
               <option value="cash on delivery">cash on delivery</option>
               <option value="credit card">credit card</option>
               <option value="paytm">paytm</option>
               <option value="paypal">paypal</option>
            </select>
         </div>
         <div class="inputBox">
            <span>address line 01 :</span>
            <input type="text" name="flat" class="box" required placeholder="e.g. flat no." maxlength="50">
         </div>
         <div class="inputBox">
            <span>address line 02 :</span>
            <input type="text" name="street" class="box" required placeholder="e.g. street name." maxlength="50">
         </div>
         <div class="inputBox">
            <span>pin code :</span>
            <input type="number" name="pin_code" class="box" required placeholder="e.g. 123456" min="0" max="999999" onkeypress="if(this.value.length == 6) return false;">
         </div>
      </div>

      <input type="submit" value="order now" class="btn" name="order">

   </form>

</section>

<!-- order section ends -->

<!-- faq section starts  -->

<section class="faq" id="faq">

   <h1 class="heading">FAQ</h1>

   <div class="accordion-container">

      <div class="accordion active">
         <div class="accordion-heading">
            <span>how does it work?</span>
            <i class="fas fa-angle-down"></i>
         </div>
         <p class="accrodion-content">
         Let us now understand the working of this system. Whenever a customer visits the webpage or mobile application of the pizzeria, he/she will have to select his location so that the order is made to the nearest pizzeria. The menu will be visible to the customer with the pizzas and other non-pizza products on offer. All the ingredients will be shown at their prices.

Now the customer will customize his pizza and make changes in the ingredients if he wishes and select the quantity for it. After selecting the items to be ordered, the customer has to select the type of order whether it will be home delivery or pick up. After that, the customer has to provide his/her details like name, phone number, address, email id.

Now payment option is shown to the customer. He has to choose from the various online payment methods or cash-on-delivery options. After this process, the order is made and the customer is notified about the time by which the pizza will be delivered to them.
         </p>
      </div>

      <div class="accordion">
         <div class="accordion-heading">
            <span>how long does it take for delivery?</span>
            <i class="fas fa-angle-down"></i>
         </div>
         <p class="accrodion-content">
         That’s why most pizza chains deliver pizzas in 20-30 minutes (or less) after receiving an order. However, the delivery time may vary based on a few factors which include: Whether there’s a driver available to deliver the pizza. The distance between the store and the customer’s house.
         </p>
      </div>

      <div class="accordion">
         <div class="accordion-heading">
            <span>can I order for huge parties?</span>
            <i class="fas fa-angle-down"></i>
         </div>
         <p class="accrodion-content">
         Pizza makes the perfect party meal or snack. With the range of crust types and topics, everyone can design a pizza they will love. If you have a group of five people, and they all eat three pizzas, you will need fifteen slices. Going by our example of eight slices to a medium-sized pizza means you will need two pizzas.We recommended always ordering a little bit more than what you calculate. It is always better to have some leftover pizza than to leave any of your guests hungry. 
         </p>
      </div>

      <div class="accordion">
         <div class="accordion-heading">
            <span>how much protein it contains?</span>
            <i class="fas fa-angle-down"></i>
         </div>
         <p class="accrodion-content">
         In terms of protein, pizzas are low in fat and contain around 18 grams of protein per serving.The protein content in a typical pizza varies, but it is still an excellent source of nutrition. A regular slice contains 13 to 22 grams of protein or about 25% of the recommended daily value in a 2,000-calorie diet.
         ,a slice of pizza is a great meal choice for busy adults, and those who are watching their calories should opt for healthier versions of the classics.
         </p>
      </div>


      <div class="accordion">
         <div class="accordion-heading">
            <span>is it cooked with oil?</span>
            <i class="fas fa-angle-down"></i>
         </div>
         <p class="accrodion-content">
         Many people opt for a savory Italian style when making homemade pizza dough. This method uses olive oil in water, allowing the flour, sugar, and yeast components to absorb moisture. While adding oil to your dough will give you a better, crispier crust, there are specific ways to avoid oily dough. Using flour on the top of the dough will enhance its stretchable properties and keep moisture in while putting the oil on top will prevent it from losing its flavor. 
         </p>
      </div>

   </div>

</section>

<!-- faq section ends -->

<!-- footer section starts  -->

<section class="footer">

   <div class="box-container">

      <div class="box">
         <i class="fas fa-phone"></i>
         <h3>phone number</h3>
         <p>+91-983764637</p>
         <p>+080-88888</p>
      </div>

      <div class="box">
         <i class="fas fa-map-marker-alt"></i>
         <h3>our address</h3>
         <p>Bengaluru,India 560100</p>
      </div>

      <div class="box">
         <i class="fas fa-clock"></i>
         <h3>opening hours</h3>
         <p>09:00am to 11:00pm</p>
      </div>

      <div class="box">
         <i class="fas fa-envelope"></i>
         <h3>email address</h3>
         <p>adhitimr@gmail.com</p>
         <p>amulyayr@gmail.com</p>
      </div>

   </div>

   <div class="credit">
      &copy; copyright @ <?= date('Y'); ?> by <span>mr. web designer</span> | all rights reserved!
   </div>

</section>

<!-- footer section ends -->



















<!-- custom js file link  -->
<script src="js/script.js"></script>

</body>
</html>