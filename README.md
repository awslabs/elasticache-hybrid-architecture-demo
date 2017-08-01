# Reduce latency of hybrid architectures with Amazon ElastiCache

Demo code to deploy and test Amazon ElastiCache in hybrid architectures with database living on-premises or in AWS.

## Launch of AWS CloudFormation template

To deploy and test the template:

1. Download the template by cloning the repo:

   **git clone https://github.com/awslabs/elasticache-hybrid-architecture-demo**

2. Log into AWS Console
3. Go to AWS Cloudformation's console and click on "Create Stack"
4. Select "Upload a template to Amazon S3" and choose the file "cloudformation-template.yaml", click Next
5. Give a name to your Stack like "ElastiCacheDemo", fill the Parameters and click Next, the most important parameters are:

   | Parameter        | Description                                           |
   | ---------------- | ----------------------------------------------------- |
   | DatabaseServer   | It is your database address, IP or hostname           |
   | DatabaseName     | Name of your database or schema                       |
   | DatabaseUsername | Your database's username                              |
   | KeyName          | Your key pair to SSH access the instance              |
   | Vpc              | VPC where your Amazon EC2 instance will be deployed   |
   | Subnet           | Subnet where your Amazon EC2 instance will be deployed|

6. Select the Tags to include in your Stack and click Next

7. Review your options, select the "I acknowledge that AWS CloudFormation might create IAM resources." and click on "Create"

8. Once the Stack is ready, go to "Outputs", copy the "DemoScript" URL and open it in your web browser

**Test your queries directly from your database as well as using cache to compare response times!**
