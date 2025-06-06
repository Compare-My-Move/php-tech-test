# PHP Developer Role - Technical Test 
We have a network of companies that we would like to be able to match with users looking for services in their area. The network is still growing and right now and we only have companies covering Birmingham (B), Bristol (BS) and Cardiff (CF). 

Unfortunately the dev looking after this project ran into some trouble and committed some broken and unfinished code. Can you help us out?

## Prerequisites  
1. A LAMP environment running PHP ^7
2. Set the DocumentRoot for the project in your Apache config or virtual hosts config
3. Import companies and matching settings from *project.sql* into a database and connect to the app

## Requirements
1. Using data submitted in *resources/views/layouts/form.twig*, find a maximum of **3 random** companies that cover:
    - the postcode prefix of the postcode entered
    - the number of bedrooms specified
    - the type specified
2. Deduct a credit from all companies matched
3. Return a view with a list of companies matched

### Tips and hints
- Getting a 500 error and not sure what's going on? What environment are you in? Do you expect to see errors?
- It looks like there are some methods in the form controller that are called but not defined. Is this unfinished or has the dev forgotten something?
- Can't connect to your database? Have you set the connection settings?
- Getting errors in your views? Have you checked your blocks?

#### Bonus points
- Prevent the form from being submitted twice by disabling the submit button on first submit
- Reveal/Hide additional company information by clicking on the **more** link on the results page
- Install a logger and log to file whenever a company runs out of credits





## Part Two:

# Thought Exercise: Extending the Matching Form for Conveyancing

This exercise explores how the existing surveyor-matching system can be extended to support **conveyancing services**. The task involves extending the current logic, maintaining scalability and maintainability, and accommodating potential future service types (e.g., surveying, conveyancing, etc.).

### Key Changes Needed
1. **Support for Multiple Services**: We need to introduce the concept of different service types (e.g., Surveying, Conveyancing).
2. **Service-Specific Form Requirements**: Conveyancing has different data requirements than surveying, so we need to customize the form fields based on the service type.
3. **Pricing Models**: Different lead pricing structures for different service types, which may vary based on pricing models (e.g., pay-per-lead or subscription).
4. **Separate Company Networks**: Each service type will have its own network of companies, meaning different companies will handle surveying leads versus conveyancing leads.

---

## 1. High-Level Architecture

```plaintext
                                +-----------------+
                                |     User Form   |
                                +--------+--------+
                                         |
                       +-----------------+-----------------+
                       |                                   |
               +-------v--------+                 +--------v--------+
               | Surveying Form |                 | Conveyancing Form|
               +-------+--------+                 +--------+--------+
                       |                                   |
               +-------v-----------------------------------v--------+
               |            Generic Matching Controller             |
               +-------+------------------------------+-------------+
                       |                              |
              +--------v------+              +--------v--------+
              | SurveyMatcher |              | ConveyanceMatcher|
              +---------------+              +------------------+
                       |                              |
              +--------v------+              +--------v--------+
              | CompanyService|              | CompanyService  |
              +---------------+              +------------------+
```

- The **User Form** lets the user select a service (Surveying, Conveyancing).
- Based on the selection, the appropriate form (Surveying Form or Conveyancing Form) is presented with the relevant fields.
- A **Generic Matching Controller** handles the business logic for matching users to companies. Service-specific logic is handled by **SurveyMatcher** and **ConveyanceMatcher**.
- Both **SurveyMatcher** and **ConveyanceMatcher** interact with a `CompanyService` to retrieve the relevant companies based on their credits and the type of lead.

---

## 2. Database Structure Changes

### A. New `ServiceType` Entity

We need to introduce a `ServiceType` entity to distinguish between different services (Surveying, Conveyancing, etc.).

```php
ServiceType {
    id // unique identifier
    name // 'Surveying', 'Conveyancing', etc.
}
```

### B. Update `Company` Entity

The `Company` entity must now be associated with a specific service type. Companies will only handle leads related to their service type.

```php
Company {
    id
    name
    service_type_id (FK)  // Associated service type (Surveying, Conveyancing)
    credit_balance
    ...
}
```

### C. Dynamic Form Requirements

We need to capture different form fields for each service. A new `LeadRequirement` table can handle this.

```php
LeadRequirement {
    id
    service_type_id  // Linked to Surveying or Conveyancing
    field_name       // e.g., 'postcode', 'bedrooms', 'type' for Surveying
    field_label      // Display name for the field
    field_type       // text, number, dropdown, etc.
    required         // boolean
}
```

### D. Lead Matching Table

The `Lead` and `LeadMatch` tables will store leads and the companies matched to them.

```php
Lead {
    id
    service_type_id // The type of service for which this lead was generated
    user_input_json // Serialized user input data
}

LeadMatch {
    id
    lead_id
    company_id
    matched_on_score  // How closely the company matched the lead criteria
}
```

---

## 3. Pricing Models for Different Services

Each service may have different pricing structures, so we need to support dynamic pricing models based on the service type.

### Pricing Table for Leads

```php
PricingModel {
    service_type_id  // Linked to Surveying, Conveyancing, etc.
    cost_per_lead    // Cost of a lead for this service type
    subscription_enabled // Whether subscription model is enabled
    leads_included   // Number of leads included in the subscription, if any
}
```

- **Surveying** might charge per lead.
- **Conveyancing** might offer a subscription model with a fixed number of leads per month.

---

## 4. Approach to Form Handling

- **Dynamic Form Generation**: Based on the selected service type, the form will dynamically load the fields required for that service. This is achieved by fetching the `LeadRequirement` records associated with the selected `ServiceType`.
- **User Input Handling**: Each service type will have its own validation rules and field types, which will be reflected in the frontend form.
  
### Example Workflow:

1. **User visits the form** and selects a service type (Surveying or Conveyancing).
2. **Form loads fields dynamically** based on the selected service type, querying the `LeadRequirement` table for the specific fields required.
3. **User submits the form** with data.
4. **Matching logic is executed** by the appropriate service-specific matcher (e.g., `SurveyMatcher` or `ConveyanceMatcher`).
5. Companies with available credits are matched and notified based on the lead type.

---

## 5. Challenges and Considerations

### A. Scalability
- **Horizontal scaling**: The system can scale by adding additional matcher services for new service types.
- **Database optimization**: We need to ensure that the database can efficiently handle large numbers of leads and companies, especially when dealing with high volumes of requests.

### B. Maintainability
- **Service-specific logic** should be encapsulated within their respective matchers (e.g., `SurveyMatcher`, `ConveyanceMatcher`).
- Using **Dependency Injection (DI)** will make it easy to replace or modify matching logic as the business requirements evolve.
  
### C. Extensibility
- **New services** can be added by introducing new `ServiceType` records and defining corresponding matchers.
- The **form structure** is flexible and driven by the database, allowing easy adjustments when adding new services or changing form fields.

### D. Pricing Models
- The system should allow different **pricing models** for each service type (pay-per-lead, subscription, etc.).
- We may also need to accommodate **tiered pricing** for services with different pricing per lead based on volume.

---

## 6. Potential Solutions to Consider

### A. Use of Caching
- Cache frequently requested service data (e.g., form requirements, company information) to reduce load on the database and improve performance.

### B. Async Processing for Lead Matching
- Matching a large number of companies to a single lead could be time-consuming. We can consider queuing this process and notifying the user once matching is complete.

---

## 7. Conclusion

To extend the existing surveyor-matching form to handle conveyancing, the primary focus is on generalizing the matching logic while ensuring each service type has specific form fields, pricing, and matching criteria. By introducing the `ServiceType` model and designing dynamic form generation, we can easily add support for additional service types in the future.

If we had known from the beginning that multiple services would need to be supported, we would have designed the system with greater abstraction from the start, ensuring service-specific logic is modular and easily extensible.

