FROM node:20-alpine

WORKDIR /app

# Copier package.json/lock et installer
COPY package*.json ./
RUN npm install

# Copier le reste
COPY . .

EXPOSE 3000

CMD ["npm", "run", "dev"]