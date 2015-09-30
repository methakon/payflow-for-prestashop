# Details #
This is a rough layout of the [PayPal](http://www.paypal.com) function that will process the transaction and return a status code.

## Variable Types ##
This is subject to change, please comment below if there is a better variable type to use.
  * array
  * int8
  * int16
  * varchar
  * byte
  * date
  * boolean

## Input Arrays ##
_**The module will pass this function the array's below**_
### $order\_details (array) ###
  * $order\_number (int8)
  * $sale\_amount (int8)

### $customer\_details (array) ###
  * $customer\_number (int16)
  * $email (varchar)

### $billing\_details (array) ###
  * $billing\_first\_name (varchar)
  * $billing\_last\_name (varchar)
  * $billing\_company\_name (varchar)
  * $billing\_address (varchar)
  * $billing\_city (varchar)
  * $billing\_state (varchar)
  * $billing\_zip (int8)
  * $billing\_country (byte)

### $shipping\_details (array) ###
  * $shipping\_first\_name (varchar)
  * $shipping\_last\_name (varchar)
  * $shipping\_address (varchar)
  * $shipping\_city (varchar)
  * $shipping\_state (varchar)
  * $shipping\_zip (int8)
  * $shipping\_country (byte)

### $cc\_info (array) ###
  * $credit\_card\_number (int16)
  * $expiration\_date (date)
  * $cvc\_code (byte)


## Output Array ##
_**The module will expect a retun of the variables below in a named array**_
  * $transaction\_id (varchar)
  * $authorization\_code (varchar)
  * $avs\_zip\_match (boolean)
  * $avs\_street\_match (boolean)
  * $CVC\_match (boolean)


## Constants ##
_**User editable from the module config page.  Will be passed to this function:**_
### $costants (array) ###
  * TransactionType = Sale  `\\Possible Values: Authorization, Credit, Delayed_Capture, Sale`
  * TransactionMode = Test  `\\Possible Values: Live, Test`