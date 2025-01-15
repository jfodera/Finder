# Finder

## Note to User: 

There are 2 different account types: 'users' and 'recorders'. Users can only subit lost items while recorders act as an admin (like public safety) that can create matches and add found items. Logins for each are below:

The Database the live version is connected to is a 'mock' DB. It is not filled with real lost/found items and is instead meant to be played with in order to undertand the functionality of the site. So feel free to add lost/found items or match them when logged in as a recorder. 

Link to site: https://finder.eastus.cloudapp.azure.com/finder

```
User (more lost items submitted): 
   email: kellyc9@rpi.edu
   password: password

Recorder (view matches and entire DB): 
   email: leed22@rpi.edu  
   password: password 
```

Note: We did implement an entire IAM with email verification setup and working. If you would like to test that out, feel free to make your own user account. 


## Summary of Project

We are making a lost and found app for connecting and matching similar to Tinder hence our group name finder (FIN-dur). Our project is a website designed to match lost items, found around the RPI campus, with descriptions submitted by those who have lost them. Our rationale for this project is that no apps or websites currently at RPI can quickly match you with an item you lost. Currently, you would have to physically go to the APO or Pubsafe. The APO’s website for lost and found items would be your only other option. But that gives zero immediate feedback and doesn’t do anything other than say a lost report has been submitted. When you lose something, you're often in a rush and feel desperate to find it. A simple form submission, like the one on APO's lost and found website, isn't very helpful at that moment. It doesn't give you any sort of quick response, such as ‘We may have found your item' or 'There are no items in the database that match your description’. That's what we want Finder to provide, an immediate answer to our user as in, what to do next.

Using Finder, the life cycle of a lost item would go as follows. A user would lose the item. Then someone would stumble across the item and could use Finder to tell them where to drop the item (Public Safety or the APO). Then administrative staff with passkeys into the Finder database would enter the item and its info into Finder. At this point the user would realize their item is missing, make a request on Finder and be matched with their item. They would then go to the location Finder gives them to pick up the item.

The overall goal for this project is to have an advanced lost and found website for RPI students to connect them with items they may have lost. The matching system mentioned previously is similar to Tinder in that the website asks for a query of descriptions for the item lost and it matches it to items posted as found by trusted individuals. For example, these queries would include details such as the location where the item was lost, a general description of the item, and the time it was lost. This approach creates a more reliable lost and found system than what is currently in place. Another large goal for this application is to not fall into the trap of making it similar to Facebook Marketplace. We aim to prevent situations where users can casually browse through a list of items and falsely claim something just because it looks appealing, even though it doesn't belong to them. We hope to build a secure app that allows users to rightfully and efficiently claim their lost items.

## Users and Stakeholders

### Lost Items Owners (RPI Students and Faculty)

These are the primary users, they are the ones that are trying to find the items that they lost. They submit detailed descriptions of their lost items, including location, time, and a general description. Our platform is valuable for these users as it provides a quick and reliable way to recover lost items. It is more reassuring than using the APO website as our service provides immediate feedback as we attempt to match them with an item submitted to us.

### Administrative Staff (Public Safety, APO)

These users are the ones who are typically responsible for actually returning the items. Our platform enables these individuals with an interface that allows them to update the lost item database quickly and easily. Also, the automatic matchmaking feature (person to item) means that the 'recorders' do not have to manually sort through a database to find a matching query.

### Finders Trusted Individuals: Students (with a moral compass), RA’s, Professors

These stakeholders are the ones who may not even use the app. They are the ones who stumble across a lost item and feel a need to return it to its rightful owner. Our platform enables these individuals to no longer be responsible for directly returning the item to the owner. Instead, they just have to hand it over to some sort of administrative staff (mentioned above) that has access to the database. This reduces headache, and burden, and makes the lost and found much more efficient, compared to, for example, making a random Snapchat post.

### RPI Campus Community

These stakeholders also may never use the app because they may not lose an item. They are just everyday members of the RPI community that are on campus often. Our platform would provide these stakeholders peace of mind by knowing there's a reliable way to find lost items without needing to go to Pubsafe or APO directly.

## Tech Stack

We will be using HTML, CSS, and Javascript for the front end. The backend will be powered by PHP, with MariaDB as the database to store user and item information. SQL will be used to handle database queries and interactions. We will have verification built into our account creation system to confirm that you are part of RPI and are a “trusted individual”. For the users, we will only allow you to make an account using your RPI email address. When a user registers, we will generate a unique token and send a verification email using PHP's mail() function or a library like PHPMailer. The database will include a field called verified to track whether the user has completed the email verification process. We will also have a database that stores lost items with multiple fields, including item type, location, and approximate time of the item being lost. Users can access a form on the front end to submit their lost item details, which will be matched against items in the database. The UI/UX will be designed using HTML, CSS, and JavaScript to ensure a smooth and user-friendly experience.

## Functional/Non-Functional Requirements

The core functionality involves storing detailed descriptions of lost items in a database with multiple fields to capture all necessary information. We should be able to search through the database and it should be easily scalable, appendable, and dynamic.

The product also needs a verification system to ensure that the users go to RPI, so they will provide an RPI email address. The code will send a verification email to their RPI email and users must verify their email before they can submit form queries of lost items.

To prevent spam, a submission cooldown will be implemented in which users can only submit one lost item form per 6 hours. The code will use Javascript to enforce this and will receive a popup if 6 hours have not passed. Additionally, the query cannot be edited once submitted to ensure integrity.

Recorders, who are responsible for managing and submitting lost items will need to authenticate themselves with a Recorder passkey. The passkey will be checked against a recorder passkey database to ensure that only authorized individuals can access recorder functionalities.

To match users with their lost items, the system will search the lost item database for matches between queries and recorded items. The search algorithm will be designed to determine how closely fields need to match to ensure accurate identification of lost items. This process will be dynamic to accommodate frequent updates and ensure the system remains effective over time.
